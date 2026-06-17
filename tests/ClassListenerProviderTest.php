<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassListenerProvider;
use Componenta\ClassFinder\Tests\Fixture\SpyListener;
use Componenta\ClassFinder\Tests\Fixture\SimpleListener;

it('returns all registered listeners', function () {
    $listener1 = new SpyListener();
    $listener2 = new SimpleListener();
    $provider = new ClassListenerProvider([$listener1, $listener2]);

    expect($provider->getClassListeners())->toHaveCount(2)
        ->toContain($listener1)
        ->toContain($listener2);
});

it('adds a listener dynamically', function () {
    $provider = new ClassListenerProvider();
    $listener = new SpyListener();

    $provider->addListener($listener);

    $listeners = $provider->getClassListeners();

    expect($listeners)->toHaveCount(1)
        ->and($listeners[0])->toBe($listener);
});

it('accepts an iterable in constructor', function () {
    $generator = (function () {
        yield new SpyListener();
        yield new SimpleListener();
    })();

    $provider = new ClassListenerProvider($generator);

    expect($provider->getClassListeners())->toHaveCount(2);
});


