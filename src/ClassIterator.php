<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Filter\Filterable;
use Componenta\Filter\PredicateInterface;
use Componenta\Stdlib\ReplayableIterator;
use Componenta\Tokenizer\ClassInfo;
use Componenta\Tokenizer\DeclarationType;
use RuntimeException;

/**
 * @method ClassIterator withFilter(PredicateInterface $filter, bool $prepend = false)
 * @method ClassIterator withoutFilter(PredicateInterface $filter)
 */
final class ClassIterator implements ClassIteratorInterface
{
    use Filterable {
        accept as private;
    }

    /** @var ReplayableIterator<string, ClassInfo> */
    private ReplayableIterator $iterator;

    /**
     * @param iterable<string, ClassInfo> $classes
     * @param PredicateInterface|iterable<PredicateInterface> $filters
     */
    public function __construct(
        iterable $classes,
        PredicateInterface|iterable $filters = [],
    ) {
        $this->initFilters($filters);
        $this->iterator = new ReplayableIterator($classes);
    }

    /**
     * @param array<array-key, mixed> $map Records with file, name, type and abstract/final/readonly flags.
     * @throws RuntimeException When any record is invalid; validation completes before iteration.
     */
    public static function fromMap(array $map): self
    {
        if (!array_is_list($map)) {
            throw new RuntimeException('Discovery map must be a list of declarations.');
        }

        foreach ($map as $index => $entry) {
            if (!is_array($entry)
                || !is_string($entry['file'] ?? null) || $entry['file'] === ''
                || !is_string($entry['name'] ?? null) || $entry['name'] === ''
                || !is_string($entry['type'] ?? null) || DeclarationType::tryFrom($entry['type']) === null
                || !is_bool($entry['abstract'] ?? null)
                || !is_bool($entry['final'] ?? null)
                || !is_bool($entry['readonly'] ?? null)
            ) {
                throw new RuntimeException(sprintf('Invalid discovery map declaration at index %d.', $index));
            }
        }

        return new self((static function () use ($map): \Generator {
            foreach ($map as $entry) {
                yield $entry['file'] => new ClassInfo(
                    fullyQualifiedName: $entry['name'],
                    type: DeclarationType::from($entry['type']),
                    isAbstract: $entry['abstract'],
                    isFinal: $entry['final'],
                    isReadonly: $entry['readonly'],
                );
            }
        })());
    }

    /** @return \Generator<string, ClassInfo> */
    public function getIterator(): \Generator
    {
        foreach ($this->iterator->cursor() as $filename => $classInfo) {
            if ($this->accept($classInfo, $filename)) {
                yield $filename => $classInfo;
            }
        }
    }

    /** @return list<ClassInfo> */
    public function toArray(): array
    {
        $array = [];

        foreach ($this as $classInfo) {
            $array[] = $classInfo;
        }

        return $array;
    }

    /** @return int<0, max> */
    public function count(): int
    {
        $count = 0;

        foreach ($this as $_) {
            $count++;
        }

        return $count;
    }
}
