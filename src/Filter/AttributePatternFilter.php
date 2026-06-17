<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

final class AttributePatternFilter extends AbstractFilter
{
    use SearchesClassMembers;

    private \Closure $matcher;

    public function __construct(
        private readonly string $pattern,
        private readonly bool $deepSearch = false,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);
        $this->matcher = PatternMatcher::create($this->pattern);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo) {
            return false;
        }

        if ($this->checkAttributes($value->reflector->getAttributes())) {
            return true;
        }

        if (!$this->deepSearch) {
            return false;
        }

        $matcher = $this->matcher;

        return $this->searchMemberAttributes(
            $value->reflector,
            static fn(array $attrs): bool => array_any(
                $attrs,
                static fn($attr) => $matcher($attr->getName()),
            ),
        );
    }

    private function checkAttributes(array $attributes): bool
    {
        if (empty($attributes)) {
            return false;
        }

        foreach ($attributes as $attribute) {
            if (($this->matcher)($attribute->getName())) {
                return true;
            }
        }

        return false;
    }

    public static function exactAttribute(string $attributeName, bool $deepSearch = false): self
    {
        return new self($attributeName, $deepSearch);
    }

    public static function anyAttribute(array $attributeNames, bool $deepSearch = false): AbstractFilter
    {
        return new AnyAttributeFilter($attributeNames, $deepSearch);
    }

    public static function attributePrefix(string $prefix, bool $deepSearch = false): self
    {
        return new self("$prefix*", $deepSearch);
    }
}
