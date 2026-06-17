<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Factory;

use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\ConfigKey;
use Componenta\Tokenizer\TokenizerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ClassFinderFactory
{
    public function __invoke(ContainerInterface $container): ClassFinder
    {
        $config = $container->has('config') ? $container->get('config') : [];

        $tokenizer = $container->has(TokenizerInterface::class)
            ? $container->get(TokenizerInterface::class)
            : null;

        $logger = $container->has(LoggerInterface::class)
            ? $container->get(LoggerInterface::class)
            : null;

        return new ClassFinder(
            $config[ConfigKey::FILTERS] ?? [],
            $tokenizer,
            $logger,
        );
    }
}
