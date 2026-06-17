<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

/**
 * Filters elements based on presence or absence of any attributes
 */
final class HasAnyAttributesFilter extends AbstractFilter
{
    use SearchesClassMembers;

    public function __construct(
        private readonly bool $mustHaveAttributes = true,
        private readonly bool $deepSearch = false,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo) {
            return false;
        }

        $hasAttributes = !empty($value->reflector->getAttributes());

        if ($hasAttributes) {
            return $this->mustHaveAttributes;
        }

        if (!$this->deepSearch) {
            return !$this->mustHaveAttributes;
        }

        $hasAnyInMembers = $this->searchMemberAttributes(
            $value->reflector,
            static fn(array $attrs): bool => !empty($attrs),
        );

        return $this->mustHaveAttributes ? $hasAnyInMembers : !$hasAnyInMembers;
    }
}
