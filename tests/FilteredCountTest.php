<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\UserService;
use Componenta\Filter\CallbackFilter;

it('counts the current filtered view while replaying the original source', function (): void {
    $visible = true;
    $reads = 0;
    $info = ClassInfoFactory::fromClass(UserService::class);
    $source = (static function () use (&$reads, $info): Generator {
        ++$reads;
        yield UserService::class => $info;
    })();
    $filter = new CallbackFilter(static function () use (&$visible): bool {
        return $visible;
    });
    $iterator = new ClassIterator($source, $filter);

    expect(count($iterator))->toBe(1)
        ->and($iterator->toArray())->toBe([$info]);

    $visible = false;

    expect(count($iterator))->toBe(0)
        ->and($iterator->toArray())->toBe([]);

    $visible = true;

    expect(count($iterator))->toBe(1)
        ->and($iterator->toArray())->toBe([$info])
        ->and($reads)->toBe(1);
});

it('propagates current filter failures after a successful count and can recover', function (): void {
    $state = new class () {
        public bool $broken = false;
    };
    $failure = new RuntimeException('Filter unavailable');
    $info = ClassInfoFactory::fromClass(UserService::class);
    $filter = new CallbackFilter(static function () use ($state, $failure): bool {
        if ($state->broken) {
            throw $failure;
        }

        return true;
    });
    $iterator = new ClassIterator([UserService::class => $info], $filter);

    expect(count($iterator))->toBe(1);

    $state->broken = true;

    expect(fn () => count($iterator))->toThrow($failure);

    $state->broken = false;

    expect(count($iterator))->toBe(1)
        ->and($iterator->toArray())->toBe([$info]);
});
