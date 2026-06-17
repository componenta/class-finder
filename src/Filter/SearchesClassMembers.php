<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Filter;

use ReflectionClass;

/**
 * Provides deep search through class members (methods, properties, constants).
 */
trait SearchesClassMembers
{
    /**
     * Iterates class members and passes their attributes to the callback.
     * Returns true as soon as the callback returns true.
     *
     * @param \Closure(list<\ReflectionAttribute>): bool $callback Returns true to stop search.
     */
    protected function searchMemberAttributes(ReflectionClass $reflector, \Closure $callback): bool
    {
        if (array_any($reflector->getMethods(), fn($method) => $callback($method->getAttributes()))) {
            return true;
        }

        if (array_any($reflector->getProperties(), fn($property) => $callback($property->getAttributes()))) {
            return true;
        }

        return array_any($reflector->getReflectionConstants(), fn($constant) => $callback($constant->getAttributes()));

    }
}
