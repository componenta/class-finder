<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\SubclassFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\AbstractSample;
use Componenta\ClassFinder\Tests\Fixture\ConcreteClass;
use Componenta\ClassFinder\Tests\Fixture\ChildOfConcrete;
use Componenta\ClassFinder\Tests\Fixture\NonCallableClass;
use Componenta\ClassFinder\Tests\Fixture\SampleInterface;
use Componenta\ClassFinder\Tests\Fixture\SampleTrait;

it('accepts direct subclass', function () {
    $filter = new SubclassFilter(AbstractSample::class);

    expect($filter->accept(ClassInfoFactory::fromClass(ConcreteClass::class)))->toBeTrue();
});

it('accepts indirect subclass', function () {
    $filter = new SubclassFilter(AbstractSample::class);

    expect($filter->accept(ClassInfoFactory::fromClass(ChildOfConcrete::class)))->toBeTrue();
});

it('rejects non-subclass', function () {
    $filter = new SubclassFilter(AbstractSample::class);

    expect($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
});

it('rejects interfaces and traits', function () {
    $filter = new SubclassFilter(AbstractSample::class);

    expect($filter->accept(ClassInfoFactory::fromInterface(SampleInterface::class)))->toBeFalse()
        ->and($filter->accept(ClassInfoFactory::fromTrait(SampleTrait::class)))->toBeFalse();
});

it('rejects non-ClassInfo values', function () {
    $filter = new SubclassFilter(AbstractSample::class);

    expect($filter->accept('not a ClassInfo'))->toBeFalse()
        ->and($filter->accept(null))->toBeFalse();
});
