<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

/**
 * Creates optimized matcher closures for wildcard patterns.
 *
 * Supports: exact match, prefix*, *suffix, *contains*, ?single-char, fnmatch fallback.
 */
final class PatternMatcher
{
    public static function create(string $pattern): \Closure
    {
        if (!str_contains($pattern, '*') && !str_contains($pattern, '?')) {
            return static fn(string $name): bool => $name === $pattern;
        }

        if (str_ends_with($pattern, '*') && substr_count($pattern, '*') === 1) {
            $prefix = substr($pattern, 0, -1);
            return static fn(string $name): bool => str_starts_with($name, $prefix);
        }

        if (str_starts_with($pattern, '*') && substr_count($pattern, '*') === 1) {
            $suffix = substr($pattern, 1);
            return static fn(string $name): bool => str_ends_with($name, $suffix);
        }

        if (str_starts_with($pattern, '*') && str_ends_with($pattern, '*') && substr_count($pattern, '*') === 2) {
            $substring = substr($pattern, 1, -1);
            return static fn(string $name): bool => str_contains($name, $substring);
        }

        return static fn(string $name): bool => fnmatch($pattern, $name, FNM_NOESCAPE);
    }
}
