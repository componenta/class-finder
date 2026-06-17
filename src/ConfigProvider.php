<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\ClassFinder\Factory\ClassFinderFactory;
use Componenta\ClassFinder\Factory\ClassListenerProviderFactory;
use Componenta\ClassFinder\Factory\ClassListenerNotifierFactory;

final class ConfigProvider
{
    /** @return array{dependencies: array{factories: array<string, class-string>}} */
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                'factories' => [
                    ClassFinderInterface::class => ClassFinderFactory::class,
                    ClassListenerProviderInterface::class => ClassListenerProviderFactory::class,
                    ClassListenerNotifier::class => ClassListenerNotifierFactory::class,
                ],
            ],
        ];
    }
}
