<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Compile;

/**
 * Output of a single {@see ListenerCompilerInterface::compile()} call.
 *
 * A compiler can contribute one config entry, one or more sidecar files, or
 * both. The application-level compile runner decides where and when to persist
 * these results.
 */
readonly class CompileResult
{
    /**
     * @param array<string, string> $files path => contents pairs.
     */
    public function __construct(
        public ?string $configKey = null,
        public mixed $configValue = null,
        public array $files = [],
    ) {}

    public static function empty(): self
    {
        return new self();
    }

    public static function config(string $key, mixed $value): self
    {
        return new self(configKey: $key, configValue: $value);
    }

    /**
     * @param array<string, string> $files
     */
    public static function filesOnly(array $files): self
    {
        return new self(files: $files);
    }
}
