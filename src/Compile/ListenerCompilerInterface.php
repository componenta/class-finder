<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Compile;

/**
 * Serializes one populated class-discovery listener into cacheable data.
 *
 * Framework packages implement this interface next to their attribute-driven
 * listeners. The application compile runner dispatches each listener to the
 * first compiler that supports it.
 */
interface ListenerCompilerInterface
{
    /**
     * Return true when this compiler can serialize the given listener.
     */
    public function supports(object $listener): bool;

    /**
     * Produce the serialized representation.
     *
     * @param string $cacheDir Absolute directory for sidecar file output.
     */
    public function compile(object $listener, string $cacheDir): CompileResult;
}
