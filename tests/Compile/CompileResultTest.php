<?php

declare(strict_types=1);

use Componenta\ClassFinder\Compile\CompileResult;

describe('CompileResult', function () {
    it('represents an empty compiler result', function () {
        $result = CompileResult::empty();

        expect($result->configKey)->toBeNull()
            ->and($result->configValue)->toBeNull()
            ->and($result->files)->toBe([]);
    });

    it('represents a config contribution', function () {
        $result = CompileResult::config('key', ['value']);

        expect($result->configKey)->toBe('key')
            ->and($result->configValue)->toBe(['value'])
            ->and($result->files)->toBe([]);
    });

    it('represents sidecar file output', function () {
        $result = CompileResult::filesOnly(['cache.php' => '<?php return [];']);

        expect($result->configKey)->toBeNull()
            ->and($result->files)->toBe(['cache.php' => '<?php return [];']);
    });
});
