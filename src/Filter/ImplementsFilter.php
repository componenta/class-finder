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
    /** @var list<string> */
    private readonly array $interfaces;

    /**
     * @param string|array<array-key, string> $interfaces
     * @param iterable<array-key, mixed> $iterable
     */
    public function __construct(string|array $interfaces, iterable $iterable = [])
    {
        parent::__construct($iterable);
        $this->interfaces = is_array($interfaces) ? array_values($interfaces) : [$interfaces];
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo || $value->isInterface || $value->isTrait || $this->interfaces === []) {
            return false;
        }

        $reflection = $value->reflector;

        return array_all(
            $this->interfaces,
            static fn (string $interface): bool => interface_exists($interface)
                && $reflection->implementsInterface($interface),
        );
    }

    /** @param array<array-key, string> $interfaces */
    public static function implementsAny(array $interfaces): AbstractFilter
    {
        return new ImplementsAnyFilter($interfaces);
    }
}
