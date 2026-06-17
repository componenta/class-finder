<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Compile;

/**
 * Configuration keys for class-discovery compile integration.
 */
final class ConfigKey
{
    /**
     * Aggregated list of {@see ListenerCompilerInterface} class-strings.
     *
     * Config providers from independent packages contribute compilers under
     * this key. The app-level compile runner resolves and executes them.
     */
    public const string LISTENER_COMPILERS = 'Componenta\ClassFinder\Compile::listener_compilers';
}
