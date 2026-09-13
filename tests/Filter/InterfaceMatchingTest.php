<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\ImplementsFilter;
use Componenta\ClassFinder\Filter\ImplementsAnyFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\ConcreteClass;
use Componenta\ClassFinder\Tests\Fixture\MultiInterfaceClass;
use Componenta\ClassFinder\Tests\Fixture\NonCallableClass;
use Componenta\ClassFinder\Tests\Fixture\SampleInterface;
use Componenta\ClassFinder\Tests\Fixture\AnotherInterface;

it('requires each interface independently of repeated requirements', function (
    string $class,
    array $interfaces,
    bool $accepted,
): void {
    $filter = new ImplementsFilter($interfaces);

    expect($filter->accept(ClassInfoFactory::fromClass($class)))->toBe($accepted);
})->with([
    'one interface repeated' => [ConcreteClass::class, [SampleInterface::class, SampleInterface::class], true],
    'two interfaces and a repeated one' => [MultiInterfaceClass::class, [SampleInterface::class, AnotherInterface::class, SampleInterface::class], true],
    'a distinct required interface is still missing' => [ConcreteClass::class, [SampleInterface::class, SampleInterface::class, AnotherInterface::class], false],
]);

it('matches the identity of an interface for every supported name', function (string $kind, string $variant): void {
    $alias = SampleInterface::class . 'MatchingAlias';
    if (!interface_exists($alias, false)) {
        class_alias(SampleInterface::class, $alias);
    }
    $interface = match ($variant) {
        'canonical' => SampleInterface::class,
        'case' => strtolower(SampleInterface::class),
        'leading separator' => '\\' . SampleInterface::class,
        'alias' => $alias,
        default => throw new LogicException('Unexpected name variant.'),
    };
    $filter = match ($kind) {
        'single' => new ImplementsFilter($interface),
        'all' => new ImplementsFilter([$interface, SampleInterface::class]),
        'any' => new ImplementsAnyFilter([$interface]),
        default => throw new LogicException('Unexpected filter kind.'),
    };

    expect($filter->accept(ClassInfoFactory::fromClass(ConcreteClass::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
})->with(['single', 'all', 'any'])->with(['canonical', 'case', 'leading separator', 'alias']);

it('recognizes an interface alias that becomes available after filter creation', function (string $kind): void {
    $alias = SampleInterface::class . 'LateMatchingAlias' . ucfirst($kind);
    $filter = match ($kind) {
        'single' => new ImplementsFilter($alias),
        'all' => new ImplementsFilter([$alias]),
        'any' => new ImplementsAnyFilter([$alias]),
        default => throw new LogicException('Unexpected filter kind.'),
    };
    $info = ClassInfoFactory::fromClass(ConcreteClass::class);

    expect($filter->accept($info))->toBeFalse();

    class_alias(SampleInterface::class, $alias);

    expect($filter->accept($info))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
})->with(['single', 'all', 'any']);

it('does not treat missing types or classes as implemented interfaces', function (string $kind): void {
    foreach ([SampleInterface::class . 'Missing', ConcreteClass::class, ''] as $name) {
        $filter = match ($kind) {
            'single' => new ImplementsFilter($name),
            'all' => new ImplementsFilter([$name]),
            'any' => new ImplementsAnyFilter([$name]),
            default => throw new LogicException('Unexpected filter kind.'),
        };

        expect($filter->accept(ClassInfoFactory::fromClass(ConcreteClass::class)))->toBeFalse();
    }
})->with(['single', 'all', 'any']);

it('preserves empty and partially matching interface selections', function (): void {
    $info = ClassInfoFactory::fromClass(ConcreteClass::class);
    $missing = SampleInterface::class . 'Missing';

    expect(new ImplementsFilter([])->accept($info))->toBeFalse()
        ->and(new ImplementsAnyFilter([])->accept($info))->toBeFalse()
        ->and(new ImplementsFilter([$missing, SampleInterface::class])->accept($info))->toBeFalse()
        ->and(new ImplementsAnyFilter([$missing, SampleInterface::class])->accept($info))->toBeTrue();
});
