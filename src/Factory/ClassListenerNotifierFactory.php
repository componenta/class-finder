<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Factory;

use Componenta\ClassFinder\ClassListenerProvider;
use Componenta\ClassFinder\ClassListenerProviderInterface;
use Componenta\ClassFinder\ClassListenerNotifier;
use Psr\Container\ContainerInterface;

final class ClassListenerNotifierFactory
{
    public function __invoke(ContainerInterface $container): ClassListenerNotifier
    {
        $provider = $container->has(ClassListenerProviderInterface::class)
            ? $container->get(ClassListenerProviderInterface::class)
            : new ClassListenerProvider();

        return new ClassListenerNotifier($provider);
    }
}