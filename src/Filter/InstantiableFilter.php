<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

final class InstantiableFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        return $value instanceof ClassInfo && $value->isConcrete && $value->reflector->isInstantiable();
    }
}
