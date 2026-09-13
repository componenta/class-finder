<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinderInterface;
use Componenta\ClassFinder\ClassListenerProviderInterface;
use Componenta\ClassFinder\ConfigKey;
use Componenta\ClassFinder\ConfigProvider;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\ClassFinder\Tests\Fixture\SimpleListener;
use Componenta\ClassFinder\Tests\Fixture\UserController;
use Componenta\Config\ConfigFactory;
use Componenta\Config\Environment;
use Componenta\DI\ContainerFactory;

it('reads filters and listener services from the runtime Config in the existing container', function (): void {
    $composition = (new ConfigFactory())->create(
        new Environment([]),
        new ConfigProvider(),
        static fn (): array => [
            ConfigKey::FILTERS => [PatternFilter::exactFqn(UserController::class)],
            ConfigKey::LISTENERS => [SimpleListener::class],
        ],
    );
    $container = (new ContainerFactory())->create($composition->config, $composition->dependencies);

    $classes = $container->get(ClassFinderInterface::class)->find(__DIR__ . '/Fixture')->toArray();
    expect(array_map(static fn ($info): string => $info->fullyQualifiedName, $classes))->toBe([UserController::class]);
    expect($container->get(ClassListenerProviderInterface::class)->getClassListeners())
        ->toBe([$container->get(SimpleListener::class)]);
});
