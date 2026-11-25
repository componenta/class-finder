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
        $this->attributesFlipped = array_fill_keys(array_map(
            static fn (string $name): string => strtolower(ltrim($name, '\\')),
            $attributeNames,
        ), true);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo || $this->attributesFlipped === []) {
            return false;
        }

        if (array_any($value->reflector->getAttributes(), fn($attr) => isset($this->attributesFlipped[strtolower($attr->getName())]))) {
            return true;
        }

        if (!$this->deepSearch) {
            return false;
        }

        return $this->searchMemberAttributes(
            $value->reflector,
            fn(array $attrs): bool => array_any(
                $attrs,
                fn($attr) => isset($this->attributesFlipped[strtolower($attr->getName())]),
            ),
        );
    }
}
