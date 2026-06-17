<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;

final class IsAbstractFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $value?->isAbstract ?? false;
    }
}
