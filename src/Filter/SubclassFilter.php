<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

/**
 * Matches class and enum declarations using ReflectionClass::isSubclassOf().
 * Interfaces and traits are not candidates; abstract classes are allowed.
 */
final class SubclassFilter extends AbstractFilter
{
    public function __construct(
        private readonly string $parentClass,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo || $value->isInterface || $value->isTrait) {
            return false;
        }

        return $value->reflector->isSubclassOf($this->parentClass);
    }
}
