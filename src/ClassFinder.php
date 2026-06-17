<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Filter\Filterable;
use Componenta\Filter\FilterInterface;
use Componenta\Filter\FilterableInterface;
use Componenta\Tokenizer\ClassInfo;
use Componenta\Tokenizer\Tokenizer;
use Componenta\Tokenizer\TokenizerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\Finder\Finder;

/**
 * @method ClassFinder withFilter(FilterInterface $filter, bool $prepend = false)
 * @method ClassFinder withoutFilter(FilterInterface $filter)
 */
final class ClassFinder implements ClassFinderInterface, FilterableInterface
{
    use Filterable {
        accept as private;
    }

    private TokenizerInterface $tokenizer;
    private LoggerInterface $logger;

    /**
     * @param iterable<FilterInterface>|FilterInterface $filters
     *
     * @throws \InvalidArgumentException If any provided filter does not implement FilterInterface.
     */
    public function __construct(
        iterable|FilterInterface $filters = [],
        ?TokenizerInterface $tokenizer = null,
        ?LoggerInterface $logger = null,
    ) {
        $this->initFilters($filters);
        $this->tokenizer = $tokenizer ?? new Tokenizer();
        $this->logger = $logger ?? new NullLogger();
    }

    /**
     * @param string|string[] $dirs
     * @param string|string[] $exclude
     * @param int-mask-of<TokenizerInterface::SEARCH_*> $mode
     */
    public function find(
        string|array $dirs,
        string|array $exclude = [],
        int $mode = TokenizerInterface::SEARCH_ALL,
    ): ClassIterator {
        return new ClassIterator(
            $this->createGenerator($dirs, $exclude, $mode),
            $this->filters,
        );
    }

    /** @return \Generator<string, ClassInfo> */
    private function createGenerator(
        string|array $dirs,
        string|array $exclude,
        int $mode,
    ): \Generator {
        $finder = new Finder()
            ->in($dirs)
            ->files()
            ->exclude($exclude)
            ->name('*.php');

        foreach ($finder as $file) {
            $filePath = $file->getRealPath();

            if ($filePath === false) {
                continue;
            }

            try {
                $content = $file->getContents();
                $declarations = $this->tokenizer->parse($content, $mode);

                foreach ($declarations as $declaration) {
                    yield $filePath => $declaration;
                }
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to parse file: {file}', [
                    'file' => $filePath,
                    'exception' => $e,
                ]);
            }
        }
    }
}