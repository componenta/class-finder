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

    private readonly array $attributesFlipped;

    public function __construct(
        array $attributeNames,
        private readonly bool $deepSearch = false,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
        $this->attributesFlipped = array_flip($attributeNames);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo) {
            return false;
        }

        if (array_any($value->reflector->getAttributes(), fn($attr) => isset($this->attributesFlipped[$attr->getName()]))) {
            return true;
        }

        if (!$this->deepSearch) {
            return false;
        }

        return $this->searchMemberAttributes(
            $value->reflector,
            fn(array $attrs): bool => array_any(
                $attrs,
                fn($attr) => isset($this->attributesFlipped[$attr->getName()]),
            ),
        );
    }
}
