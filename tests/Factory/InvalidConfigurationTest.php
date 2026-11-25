<?php

declare(strict_types=1);

use Componenta\ClassFinder\ConfigKey;
use Componenta\ClassFinder\Factory\ClassFinderFactory;
use Componenta\ClassFinder\Factory\ClassListenerProviderFactory;
use Componenta\Config\Config;
use Componenta\Config\Environment;
use Psr\Container\ContainerInterface;

function classFinderConfigContainer(mixed $configuration, bool $present = true): ContainerInterface
{
    return new class($configuration, $present) implements ContainerInterface {
        public function __construct(private mixed $configuration, private bool $present) {}
        public function has(string $id): bool { return $this->present && $id === 'config'; }
        public function get(string $id): mixed
        {
            if (!$this->has($id)) {
                throw new RuntimeException('Unknown service.');
            }

            return $this->configuration;
        }
    };
}

it('rejects an invalid registered config service for both discovery factories', function (mixed $config): void {
    $container = classFinderConfigContainer($config);

    expect(fn () => (new ClassFinderFactory())($container))->toThrow(InvalidArgumentException::class, 'Service "config"')
        ->and(fn () => (new ClassListenerProviderFactory())($container))->toThrow(InvalidArgumentException::class, 'Service "config"');
})->with([null, false, 'invalid', new stdClass()]);

it('rejects explicit malformed entries in arrays and Config objects', function (bool $runtimeConfig, mixed $value): void {
    $entries = [ConfigKey::FILTERS => $value, ConfigKey::LISTENERS => $value];
    $config = $runtimeConfig ? new Config($entries, new Environment([])) : $entries;
    $container = classFinderConfigContainer($config);

    expect(fn () => (new ClassFinderFactory())($container))->toThrow(InvalidArgumentException::class, ConfigKey::FILTERS)
        ->and(fn () => (new ClassListenerProviderFactory())($container))->toThrow(InvalidArgumentException::class, ConfigKey::LISTENERS);
})->with([false, true])->with([null, false, 'invalid']);

it('keeps default discovery and no listeners when the config service is absent', function (): void {
    $container = classFinderConfigContainer(null, false);

    expect((new ClassFinderFactory())($container)->find(dirname(__DIR__) . '/Fixture/ScanTarget')->count())->toBe(5)
        ->and((new ClassListenerProviderFactory())($container)->getClassListeners())->toBe([]);
});
