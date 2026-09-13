<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Filter\Filterable;
use Componenta\Filter\PredicateInterface;
use Componenta\Stdlib\ReplayableIterator;
use Componenta\Tokenizer\ClassInfo;

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
