<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

final class CallableFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if ($value instanceof ClassInfo && $value->exists()) {
            return method_exists($value->fullyQualifiedName, '__invoke');
        }

        return false;
    }
}
