<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\ConfigKey;
use Componenta\ClassFinder\Factory\ClassFinderFactory;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\ClassFinder\Tests\Fixture\UserController;
use Componenta\Filter\PredicateInterface;
use Psr\Container\ContainerInterface;

it('builds a class finder with filters configured through ConfigKey', function () {
    $container = new class implements ContainerInterface {
        public function get(string $id): mixed
        {
            if ($id === 'config') {
                return [
                    ConfigKey::FILTERS => [
                        PatternFilter::exactFqn(UserController::class),
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

    $finder = (new ClassFinderFactory())($container);
    $classes = $finder->find(dirname(__DIR__) . '/Fixture')->toArray();

    expect($finder)->toBeInstanceOf(ClassFinder::class)
        ->and(array_map(
            static fn ($info): string => $info->fullyQualifiedName,
            $classes,
        ))->toBe([UserController::class]);
});

it('accepts pure predicates from configured filters', function () {
    $predicate = new class implements PredicateInterface {
        public function accept(mixed $value, string|int|null $key = null): bool
        {
            return $value->fullyQualifiedName === UserController::class;
        }
    };

    $container = new class($predicate) implements ContainerInterface {
        public function __construct(private readonly PredicateInterface $predicate)
        {
        }

        public function get(string $id): mixed
        {
            if ($id === 'config') {
                return [ConfigKey::FILTERS => [$this->predicate]];
            }

            throw new RuntimeException(sprintf('Unknown service "%s"', $id));
        }

        public function has(string $id): bool
        {
            return $id === 'config';
        }
    };

    $classes = (new ClassFinderFactory())($container)
        ->find(dirname(__DIR__) . '/Fixture')
        ->toArray();

    expect(array_map(
        static fn ($info): string => $info->fullyQualifiedName,
        $classes,
    ))->toBe([UserController::class]);
});
