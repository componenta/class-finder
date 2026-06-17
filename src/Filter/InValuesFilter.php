<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

/**
 * Filters elements whose name matches any value in the provided set (OR logic)
 */
final class InValuesFilter extends AbstractFilter
{
    private readonly array $valueSet;

    public function __construct(
        array $values,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
        $this->valueSet = array_flip($values);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo) {
            return false;
        }

        return isset($this->valueSet[$value->name]);
    }
}
