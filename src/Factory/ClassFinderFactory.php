<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Factory;

use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\ConfigKey;
use Componenta\Config\Config;
use Componenta\Filter\PredicateInterface;
use Componenta\Tokenizer\TokenizerInterface;
use InvalidArgumentException;
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

        $tokenizer = null;
        if ($container->has(TokenizerInterface::class)) {
            $tokenizer = $container->get(TokenizerInterface::class);
            if (!$tokenizer instanceof TokenizerInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Service "%s" must implement %s, got %s.',
                    TokenizerInterface::class,
                    TokenizerInterface::class,
                    get_debug_type($tokenizer),
                ));
            }
        }

        $logger = null;
        if ($container->has(LoggerInterface::class)) {
            $logger = $container->get(LoggerInterface::class);
            if (!$logger instanceof LoggerInterface) {
                throw new InvalidArgumentException(sprintf(
                    'Service "%s" must implement %s, got %s.',
                    LoggerInterface::class,
                    LoggerInterface::class,
                    get_debug_type($logger),
                ));
            }
        }

        return new ClassFinder(
            $filters,
            $tokenizer,
            $logger,
        );
    }
}
