<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;
use ReflectionClass;

/**
 * Attribute filter with AND/OR logic, patterns, and deep search through class members.
 */
final class AttributeSearchFilter extends AbstractFilter
{
    use SearchesClassMembers;

    private array $attributeClasses = [];
    private array $attributePatterns = [];
    /** @var list<\Closure> */
    private array $patternMatchers = [];
    private bool $hasAttributeClasses = false;
    private bool $hasAttributePatterns = false;
    private int $totalSearches;

    public function __construct(
        private readonly array $attributes = [],
        private readonly bool $matchAll = false,
        private readonly bool $deepSearch = false,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);

        $this->categorizeAttributes();
        $this->totalSearches = count($this->attributes);
        $this->createPatternMatchers();
    }

    private function categorizeAttributes(): void
    {
        foreach ($this->attributes as $attr) {
            if ($this->isPattern($attr)) {
                $this->attributePatterns[] = $attr;
            } else {
                $this->attributeClasses[] = $attr;
            }
        }

        $this->hasAttributeClasses = !empty($this->attributeClasses);
        $this->hasAttributePatterns = !empty($this->attributePatterns);
    }

    private function isPattern(string $value): bool
    {
        return str_contains($value, '*') || str_contains($value, '?');
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo) {
            return false;
        }

        if ($this->totalSearches === 0) {
            return false;
        }

        [$matchedClasses, $matchedPatterns] = $this->checkElement($value->reflector);
        $matchCount = count($matchedClasses) + count($matchedPatterns);

        if (!$this->matchAll && $matchCount > 0) {
            return true;
        }

        if ($this->matchAll && $matchCount === $this->totalSearches) {
            return true;
        }

        if (!$this->deepSearch) {
            return false;
        }

        $deepMatchCount = $this->searchInMembersForMatches(
            $value->reflector,
            $matchedClasses,
            $matchedPatterns,
        );

        return $this->matchAll
            ? $deepMatchCount === $this->totalSearches
            : $deepMatchCount > 0;
    }

    /** @return array{array<int, true>, array<int, true>} */
    private function checkElement(ReflectionClass $reflector): array
    {
        $matchedClasses = [];
        $matchedPatterns = [];

        if ($this->hasAttributeClasses) {
            foreach ($this->attributeClasses as $idx => $attributeClass) {
                if (!empty($reflector->getAttributes($attributeClass))) {
                    $matchedClasses[$idx] = true;

                    if (!$this->matchAll) {
                        return [$matchedClasses, $matchedPatterns];
                    }
                }
            }
        }

        if ($this->hasAttributePatterns) {
            $allAttributes = $reflector->getAttributes();

            if (!empty($allAttributes)) {
                $attributeNames = array_map(
                    static fn(\ReflectionAttribute $attr) => $attr->getName(),
                    $allAttributes
                );

                foreach ($this->patternMatchers as $idx => $matcher) {
                    foreach ($attributeNames as $name) {
                        if ($matcher($name)) {
                            $matchedPatterns[$idx] = true;

                            if (!$this->matchAll) {
                                return [$matchedClasses, $matchedPatterns];
                            }
                            break;
                        }
                    }
                }
            }
        }

        return [$matchedClasses, $matchedPatterns];
    }

    /**
     * @param array<int, true> $matchedClasses Already matched attribute class indices
     * @param array<int, true> $matchedPatterns Already matched pattern indices
     */
    private function searchInMembersForMatches(
        ReflectionClass $reflector,
        array $matchedClasses,
        array $matchedPatterns,
    ): int {
        $totalMatched = count($matchedClasses) + count($matchedPatterns);

        if ($totalMatched >= $this->totalSearches) {
            return $totalMatched;
        }

        $foundClasses = $matchedClasses;
        $foundPatterns = $matchedPatterns;

        $this->searchMemberAttributes(
            $reflector,
            function (array $attributes) use (&$foundClasses, &$foundPatterns): bool {
                $this->checkMemberAttributes($attributes, $foundClasses, $foundPatterns);

                $total = count($foundClasses) + count($foundPatterns);

                if (!$this->matchAll && $total > 0) {
                    return true;
                }

                return $this->matchAll && $total >= $this->totalSearches;
            }
        );

        return count($foundClasses) + count($foundPatterns);
    }

    private function checkMemberAttributes(array $attributes, array &$foundClasses, array &$foundPatterns): void
    {
        if ($this->hasAttributeClasses) {
            foreach ($attributes as $attribute) {
                $attrName = $attribute->getName();

                foreach ($this->attributeClasses as $idx => $attributeClass) {
                    if (isset($foundClasses[$idx])) {
                        continue;
                    }

                    if ($attrName === $attributeClass) {
                        $foundClasses[$idx] = true;
                    }
                }
            }
        }

        if ($this->hasAttributePatterns && !empty($attributes)) {
            foreach ($attributes as $attribute) {
                $attrName = $attribute->getName();

                foreach ($this->patternMatchers as $idx => $matcher) {
                    if (isset($foundPatterns[$idx])) {
                        continue;
                    }

                    if ($matcher($attrName)) {
                        $foundPatterns[$idx] = true;
                    }
                }
            }
        }
    }

    private function createPatternMatchers(): void
    {
        foreach ($this->attributePatterns as $pattern) {
            $this->patternMatchers[] = PatternMatcher::create($pattern);
        }
    }

    public static function hasAttribute(string $attributeClass, bool $deepSearch = false): self
    {
        return new self([$attributeClass], matchAll: false, deepSearch: $deepSearch);
    }

    public static function hasAnyAttributes(bool $mustHaveAttributes = true, bool $deepSearch = false): AbstractFilter
    {
        return new HasAnyAttributesFilter($mustHaveAttributes, $deepSearch);
    }

    public static function hasAnyAttribute(array $attributes, bool $deepSearch = false): self
    {
        return new self($attributes, matchAll: false, deepSearch: $deepSearch);
    }

    public static function hasAllAttributes(array $attributes, bool $deepSearch = false): self
    {
        return new self($attributes, matchAll: true, deepSearch: $deepSearch);
    }
}
