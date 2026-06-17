<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use Componenta\Filter\AbstractFilter;
use Componenta\Tokenizer\ClassInfo;

/**
 * Smart filter that automatically detects search target by pattern
 *
 * Performs pattern matching using wildcard patterns (*Route*, prefix*, *suffix).
 * Automatically determines search target based on pattern structure:
 * - Patterns without '\' search in class name: *Controller, Abstract*, Test*
 * - Patterns with '\' at end search in namespace: App\Controllers\*, App\*
 * - Complex patterns with '\' search in FQN: *\Api\*Controller, App\*\*Service
 */
final class PatternFilter extends AbstractFilter
{
    private const string TARGET_NAME = 'name';
    private const string TARGET_NAMESPACE = 'namespace';
    private const string TARGET_FQN = 'fqn';

    private \Closure $subjectExtractor;
    private \Closure $matcher;
    private(set) string $detectedTarget;

    public function __construct(
        private(set) readonly string $pattern,
        iterable $iterable = []
    ) {
        parent::__construct($iterable);

        $this->detectedTarget = $this->detectSearchTarget($pattern);
        $this->subjectExtractor = $this->createSubjectExtractor($this->detectedTarget);
        $this->matcher = $this->createMatcher($pattern);
    }

    public function accept(mixed $value, string|int|null $key = null): bool
    {
        if (!$value instanceof ClassInfo) {
            return false;
        }

        $subject = ($this->subjectExtractor)($value);

        if ($subject === '' || $subject === null) {
            return false;
        }

        return ($this->matcher)($subject);
    }

    private function detectSearchTarget(string $pattern): string
    {
        if (!str_contains($pattern, '\\')) {
            return self::TARGET_NAME;
        }

        if ($this->isSimpleNamespacePattern($pattern)) {
            return self::TARGET_NAMESPACE;
        }

        return self::TARGET_FQN;
    }

    private function isSimpleNamespacePattern(string $pattern): bool
    {
        if (str_ends_with($pattern, '\\*') && substr_count($pattern, '*') === 1) {
            return true;
        }

        return false;
    }

    private function createSubjectExtractor(string $target): \Closure
    {
        return match ($target) {
            self::TARGET_NAME => static fn(ClassInfo $value): string => $value->name,
            self::TARGET_FQN => static fn(ClassInfo $value): string => $value->fullyQualifiedName,
            self::TARGET_NAMESPACE => static fn(ClassInfo $value): string => $value->namespace,
            default => static fn(ClassInfo $value): string => $value->name,
        };
    }

    private function createMatcher(string $pattern): \Closure
    {
        if ($this->detectedTarget === 'namespace' && str_ends_with($pattern, '\\*')) {
            $namespacePrefix = substr($pattern, 0, -2);
            return static fn(string $name): bool =>
                $name === $namespacePrefix || str_starts_with($name, $namespacePrefix . '\\');
        }

        return PatternMatcher::create($pattern);
    }

    public static function exactMatch(string $value): self
    {
        return new self($value);
    }

    public static function contains(string $substring): self
    {
        return new self("*$substring*");
    }

    public static function startsWith(string $prefix): self
    {
        return new self("$prefix*");
    }

    public static function endsWith(string $suffix): self
    {
        return new self("*$suffix");
    }

    public static function namespace(string $namespace): self
    {
        return new self("$namespace\\*");
    }

    public static function exactNamespace(string $namespace): self
    {
        return self::forTarget($namespace, self::TARGET_NAMESPACE);
    }

    public static function exactFqn(string $fullyQualifiedName): self
    {
        return self::forTarget($fullyQualifiedName, self::TARGET_FQN);
    }

    public static function fqn(string $pattern): self
    {
        return self::forTarget($pattern, self::TARGET_FQN);
    }

    public static function in(array $values): AbstractFilter
    {
        return new InValuesFilter($values);
    }

    private static function forTarget(string $pattern, string $target): self
    {
        $filter = new self($pattern);
        $filter->detectedTarget = $target;
        $filter->subjectExtractor = $filter->createSubjectExtractor($target);
        $filter->matcher = $filter->createMatcher($pattern);

        return $filter;
    }
}
