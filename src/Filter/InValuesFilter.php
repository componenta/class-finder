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
    /** @var array<string, true> */
    private readonly array $valueSet;

    /** @param array<array-key, string> $values */
    public function __construct(
        array $values,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
        $names = [];
        foreach ($values as $value) {
            if (!is_string($value)) {
                throw new \InvalidArgumentException('Class names must be strings.');
            }

            $names[$value] = true;
        }
        $this->valueSet = $names;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo) {
            return false;
        }

        return isset($this->valueSet[$value->name]);
    }
}
