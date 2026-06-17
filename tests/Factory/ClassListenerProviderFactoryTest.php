<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassListenerProvider;
use Componenta\ClassFinder\ConfigKey;
use Componenta\ClassFinder\Factory\ClassListenerProviderFactory;
use Componenta\ClassFinder\Tests\Fixture\SimpleListener;
use Componenta\ClassFinder\Tests\Fixture\SpyListener;
use Componenta\Config\Config;
use Psr\Container\ContainerInterface;

it('builds a listener provider from listener instances and service ids', function () {
    $listener = new SpyListener();
    $serviceListener = new SimpleListener();
    $container = new class ($listener, $serviceListener) implements ContainerInterface {
        public function __construct(
            private SpyListener $listener,
            private SimpleListener $serviceListener,
        ) {}

        public function get(string $id): mixed
        {
            if ($id === 'config') {
                return [
                    ConfigKey::LISTENERS => [
                        $this->listener,
                        SimpleListener::class,
                    ],
                ];
            }

            if ($id === SimpleListener::class) {
                return $this->serviceListener;
            }

            throw new RuntimeException(sprintf('Unknown service "%s"', $id));
        }

        public function has(string $id): bool
        {
            return $id === 'config' || $id === SimpleListener::class;
        }
    };

    $provider = (new ClassListenerProviderFactory())($container);

    expect($provider)->toBeInstanceOf(ClassListenerProvider::class)
        ->and($provider->getClassListeners())->toBe([$listener, $serviceListener]);
});

it('builds a listener provider from the Config object used by the runtime container', function () {
    $serviceListener = new SimpleListener();
    $container = new class ($serviceListener) implements ContainerInterface {
        public function __construct(
            private SimpleListener $serviceListener,
        ) {}

        public function get(string $id): mixed
        {
            if ($id === 'config') {
                return new Config([
                    ConfigKey::LISTENERS => [
                        SimpleListener::class,
                    ],
                ]);
            }

            if ($id === SimpleListener::class) {
                return $this->serviceListener;
            }

            throw new RuntimeException(sprintf('Unknown service "%s"', $id));
        }

        public function has(string $id): bool
        {
            return $id === 'config' || $id === SimpleListener::class;
        }
    };

    $provider = (new ClassListenerProviderFactory())($container);

    expect($provider)->toBeInstanceOf(ClassListenerProvider::class)
        ->and($provider->getClassListeners())->toBe([$serviceListener]);
});

it('rejects invalid listener config entries instead of silently ignoring them', function () {
    $container = new class implements ContainerInterface {
        public function get(string $id): mixed
        {
            if ($id === 'config') {
                return [
                    ConfigKey::LISTENERS => [
                        ['not a listener'],
                    ],
                ];
            }

            throw new RuntimeException(sprintf('Unknown service "%s"', $id));
        }

        public function has(string $id): bool
        {
            return $id === 'config';
        }
    };

    expect(fn () => (new ClassListenerProviderFactory())($container))
        ->toThrow(InvalidArgumentException::class, 'Listener entry at key "0"');
});
