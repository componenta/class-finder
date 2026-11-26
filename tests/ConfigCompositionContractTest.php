<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinderInterface;
use Componenta\ClassFinder\ClassListenerNotifier;
use Componenta\ClassFinder\ClassListenerProviderInterface;
use Componenta\ClassFinder\ConfigKey;
use Componenta\ClassFinder\ConfigProvider;
use Componenta\ClassFinder\Factory\ClassFinderFactory;
use Componenta\ClassFinder\Factory\ClassListenerNotifierFactory;
use Componenta\ClassFinder\Factory\ClassListenerProviderFactory;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\ClassFinder\Tests\Fixture\SpyListener;
use Componenta\ClassFinder\Tests\Fixture\UserController;
use Componenta\Config\ConfigFactory;
use Componenta\Config\Environment;
use Psr\Container\ContainerInterface;

it('composes discovery configuration and resolves services through their PSR-11 contracts', function (): void {
    $listener = new SpyListener();
    $predicate = PatternFilter::exactFqn(UserController::class);
    $environment = new Environment([]);
    $composition = (new ConfigFactory())->create(
        $environment,
        new ConfigProvider(),
        static fn (): array => [ConfigKey::FILTERS => [$predicate]],
        static fn (): array => [ConfigKey::LISTENERS => ['discovery.listener']],
    );

    expect($composition->config->environment)->toBe($environment)
        ->and($composition->config->get(ConfigKey::FILTERS))->toBe([$predicate])
        ->and($composition->config->get(ConfigKey::LISTENERS))->toBe(['discovery.listener'])
        ->and($composition->config->has('dependencies'))->toBeFalse()
        ->and($composition->dependencies->sections['factories'])->toBe([
            ClassFinderInterface::class => ClassFinderFactory::class,
            ClassListenerProviderInterface::class => ClassListenerProviderFactory::class,
            ClassListenerNotifier::class => ClassListenerNotifierFactory::class,
        ]);

    $container = new class($composition->config, $listener) implements ContainerInterface {
        private array $services;

        public function __construct(\Componenta\Config\Config $config, SpyListener $listener)
        {
            $this->services = ['config' => $config, 'discovery.listener' => $listener];
        }

        public function has(string $id): bool
        {
            return array_key_exists($id, $this->services);
        }

        public function get(string $id): mixed
        {
            if (!$this->has($id)) {
                throw new RuntimeException('Unexpected service lookup: ' . $id);
            }

            return $this->services[$id];
        }

        public function register(string $id, object $service): void
        {
            $this->services[$id] = $service;
        }
    };

    $classes = (new ClassFinderFactory())($container)->find(__DIR__ . '/Fixture');
    $provider = (new ClassListenerProviderFactory())($container);
    $container->register(ClassListenerProviderInterface::class, $provider);
    (new ClassListenerNotifierFactory())($container)->notify($classes);

    expect($provider->getClassListeners())->toBe([$listener])
        ->and(array_map(static fn ($info) => $info->fullyQualifiedName, $classes->toArray()))
        ->toBe([UserController::class])
        ->and($listener->handled)->toBe($classes->toArray())
        ->and($listener->finalized)->toBeTrue();
});
