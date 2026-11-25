<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

final class CallableFilter extends AbstractFilter
{
    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo || (!$value->isConcrete && !$value->isEnum)) {
            return false;
        }

        // Resolve the declaration through the same reflection boundary as other
        // runtime predicates. Do not require it to have been loaded already.
        $reflection = $value->reflector;

        return !$reflection->isAbstract()
            && $reflection->hasMethod('__invoke')
            && $reflection->getMethod('__invoke')->isPublic();
    }
}
