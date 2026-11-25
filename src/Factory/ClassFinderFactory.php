<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Factory;

use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\ConfigKey;
use Componenta\Config\Config;
use Componenta\Filter\PredicateInterface;
use InvalidArgumentException;
use Componenta\Tokenizer\TokenizerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

final class ClassFinderFactory
{
    public function __invoke(ContainerInterface $container): ClassFinder
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $filters = match (true) {
            $config instanceof Config => $config->get(ConfigKey::FILTERS, []),
            is_array($config) => array_key_exists(ConfigKey::FILTERS, $config) ? $config[ConfigKey::FILTERS] : [],
            default => throw new InvalidArgumentException(sprintf('Service "config" must be an array or %s, got %s.', Config::class, get_debug_type($config))),
        };

        if (!$filters instanceof PredicateInterface && !is_iterable($filters)) {
            throw new InvalidArgumentException(sprintf(
                'Configuration "%s" must be a predicate or an iterable of predicates, got %s.',
                ConfigKey::FILTERS,
                get_debug_type($filters),
            ));
        }

        $tokenizer = $container->has(TokenizerInterface::class)
            ? $container->get(TokenizerInterface::class)
            : null;

        $logger = $container->has(LoggerInterface::class)
            ? $container->get(LoggerInterface::class)
            : null;

        return new ClassFinder(
            $filters,
            $tokenizer,
            $logger,
        );
    }
}
