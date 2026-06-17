<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

/**
 * Filters classes that implement ANY of the specified interfaces (OR logic)
 */
final class ImplementsAnyFilter extends AbstractFilter
{
    private readonly array $interfacesFlipped;

    public function __construct(
        array $interfaces,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
        $this->interfacesFlipped = array_flip($interfaces);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo || $value->isInterface || $value->isTrait) {
            return false;
        }

        return array_any($value->reflector->getInterfaceNames(), fn($interface) => isset($this->interfacesFlipped[$interface]));

    }
}
