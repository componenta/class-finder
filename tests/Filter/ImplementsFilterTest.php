<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\ImplementsFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\ConcreteClass;
use Componenta\ClassFinder\Tests\Fixture\MultiInterfaceClass;
use Componenta\ClassFinder\Tests\Fixture\NonCallableClass;
use Componenta\ClassFinder\Tests\Fixture\SampleInterface;
use Componenta\ClassFinder\Tests\Fixture\AnotherInterface;
use Componenta\ClassFinder\Tests\Fixture\SampleTrait;

it('accepts a class implementing the interface', function () {
    $filter = new ImplementsFilter(SampleInterface::class);

    expect($filter->accept(ClassInfoFactory::fromClass(ConcreteClass::class)))->toBeTrue();
});

it('rejects a class not implementing the interface', function () {
    $filter = new ImplementsFilter(SampleInterface::class);

    expect($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
});

it('rejects interfaces and traits', function () {
    $filter = new ImplementsFilter(SampleInterface::class);

    expect($filter->accept(ClassInfoFactory::fromInterface(SampleInterface::class)))->toBeFalse()
        ->and($filter->accept(ClassInfoFactory::fromTrait(SampleTrait::class)))->toBeFalse();
});

it('requires all interfaces when given an array (AND logic)', function () {
    $filter = new ImplementsFilter([SampleInterface::class, AnotherInterface::class]);

    expect($filter->accept(ClassInfoFactory::fromClass(MultiInterfaceClass::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(ConcreteClass::class)))->toBeFalse();
});

it('implementsAny accepts class with any matching interface (OR logic)', function () {
    $filter = ImplementsFilter::implementsAny([SampleInterface::class, AnotherInterface::class]);

    expect($filter->accept(ClassInfoFactory::fromClass(MultiInterfaceClass::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(ConcreteClass::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
});

it('rejects non-ClassInfo values', function () {
    $filter = new ImplementsFilter(SampleInterface::class);

    expect($filter->accept('not a ClassInfo'))->toBeFalse()
        ->and($filter->accept(null))->toBeFalse();
});
