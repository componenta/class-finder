<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Tokenizer\ClassInfo;
use Componenta\Filter\Filterable;
use Componenta\Filter\FilterInterface;
use Componenta\Stdlib\ReplayableIterator;

/**
 * @method ClassIterator withFilter(FilterInterface $filter, bool $prepend = false)
 * @method ClassIterator withoutFilter(FilterInterface $filter)
 */
final class ClassIterator implements ClassIteratorInterface
{
    use Filterable {
        accept as private;
    }

    private ?int $cachedCount = null;

    /** @var ReplayableIterator<string, ClassInfo> */
    private ReplayableIterator $iterator;

    /**
     * @param iterable<string, ClassInfo> $classes
     * @param FilterInterface|iterable<FilterInterface> $filters
     *
     * @throws \InvalidArgumentException If any provided filter does not implement FilterInterface.
     */
    public function __construct(
        iterable $classes,
        FilterInterface|iterable $filters = [],
    ) {
        $this->initFilters($filters);
        $this->iterator = new ReplayableIterator($classes);
    }

    public function __clone(): void
    {
        $this->cachedCount = null;
    }

    /** @return \Generator<string, ClassInfo> */
    public function getIterator(): \Generator
    {
        foreach ($this->iterator as $filename => $classInfo) {
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
        if ($this->cachedCount !== null) {
            return $this->cachedCount;
        }

        $count = 0;

        foreach ($this as $_) {
            $count++;
        }

        return $this->cachedCount = $count;
    }
}