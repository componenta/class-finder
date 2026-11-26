<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

/**
 * Filters elements that have any of the specified attributes (OR logic)
 */
final class AnyAttributeFilter extends AbstractFilter
{
    use SearchesClassMembers;

    /** @var array<string, true> */
    private readonly array $attributesFlipped;

    /** @param array<array-key, string> $attributeNames */
    public function __construct(
        array $attributeNames,
        private readonly bool $deepSearch = false,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
        $names = [];
        foreach ($attributeNames as $name) {
            if (!is_string($name)) {
                throw new \InvalidArgumentException('Attribute names must be strings.');
            }

            $names[strtolower(ltrim($name, '\\'))] = true;
        }
        $this->attributesFlipped = $names;
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo || $this->attributesFlipped === []) {
            return false;
        }

        $reflector = $value->reflector;
        if (array_any($reflector->getAttributes(), fn($attr) => isset($this->attributesFlipped[strtolower($attr->getName())]))) {
            return true;
        }

        if (!$this->deepSearch) {
            return false;
        }

        return $this->searchMemberAttributes(
            $reflector,
            fn(array $attrs): bool => array_any(
                $attrs,
                fn($attr) => isset($this->attributesFlipped[strtolower($attr->getName())]),
            ),
        );
    }
}
