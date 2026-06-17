<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

/**
 * String = single interface. Array = ALL interfaces (AND logic).
 * Use implementsAny() for OR logic.
 */
final class ImplementsFilter extends AbstractFilter
{
    private readonly array $interfacesFlipped;
    private readonly bool $isArray;
    private readonly int $interfaceCount;

    public function __construct(
        private readonly string|array $interfaces,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);

        $this->isArray = is_array($this->interfaces);

        if ($this->isArray) {
            $this->interfacesFlipped = array_flip($this->interfaces);
            $this->interfaceCount = count($this->interfaces);
        } else {
            $this->interfacesFlipped = [$this->interfaces => 0];
            $this->interfaceCount = 1;
        }
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo || $value->isInterface || $value->isTrait) {
            return false;
        }

        $implementedInterfaces = $value->reflector->getInterfaceNames();

        if ($this->isArray && $this->interfaceCount > count($implementedInterfaces)) {
            return false;
        }

        if ($this->isArray) {
            $found = 0;
            foreach ($implementedInterfaces as $interface) {
                if (isset($this->interfacesFlipped[$interface])) {
                    if (++$found === $this->interfaceCount) {
                        return true;
                    }
                }
            }
            return false;
        }

        foreach ($implementedInterfaces as $interface) {
            if ($interface === $this->interfaces) {
                return true;
            }
        }

        return false;
    }

    public static function implementsAny(array $interfaces): AbstractFilter
    {
        return new ImplementsAnyFilter($interfaces);
    }
}
