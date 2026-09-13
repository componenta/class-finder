<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

/** Filters classes that implement ANY of the specified interfaces (OR logic). */
final class ImplementsAnyFilter extends AbstractFilter
{
    /** @var list<string> */
    private readonly array $interfaces;

    /**
     * @param array<array-key, string> $interfaces
     * @param iterable<array-key, mixed> $iterable
     */
    public function __construct(array $interfaces, iterable $iterable = [])
    {
        parent::__construct($iterable);
        $this->interfaces = array_values($interfaces);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo || $value->isInterface || $value->isTrait) {
            return false;
        }

        $reflection = $value->reflector;

        return array_any(
            $this->interfaces,
            static fn (string $interface): bool => interface_exists($interface)
                && $reflection->implementsInterface($interface),
        );
    }
}
