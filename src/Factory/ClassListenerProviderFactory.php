<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Factory;

use Componenta\ClassFinder\ClassListenerInterface;
use Componenta\ClassFinder\ClassListenerProvider;
use Componenta\ClassFinder\ConfigKey;
use Componenta\Config\Config;
use Psr\Container\ContainerInterface;

final class ClassListenerProviderFactory
{
    /**
     * @throws \InvalidArgumentException If a resolved listener does not implement ClassListenerInterface.
     */
    public function __invoke(ContainerInterface $container): ClassListenerProvider
    {
        $config = $container->has('config') ? $container->get('config') : null;

        if ($config instanceof Config) {
            $listenerEntries = $config->get(ConfigKey::LISTENERS, []);
        } elseif (is_array($config)) {
            $listenerEntries = $config[ConfigKey::LISTENERS] ?? [];
        } else {
            return new ClassListenerProvider();
        }

        if (!is_array($listenerEntries) || $listenerEntries === []) {
            return new ClassListenerProvider();
        }

        $listeners = [];

        foreach ($listenerEntries as $key => $listener) {
            if ($listener instanceof ClassListenerInterface) {
                $listeners[] = $listener;

                continue;
            }

            if (!is_string($listener)) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Listener entry at key "%s" must be %s instance or service id string, got %s',
                        $key,
                        ClassListenerInterface::class,
                        get_debug_type($listener),
                    ),
                );
            }

            $resolved = $container->get($listener);

            if (!$resolved instanceof ClassListenerInterface) {
                throw new \InvalidArgumentException(
                    sprintf(
                        'Listener "%s" (key: %s) must implement %s, got %s',
                        $listener,
                        $key,
                        ClassListenerInterface::class,
                        get_debug_type($resolved),
                    ),
                );
            }

            $listeners[] = $resolved;
        }

        return new ClassListenerProvider($listeners);
    }
}
