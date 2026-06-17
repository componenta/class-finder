<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\InstantiableFilter;
use Componenta\ClassFinder\Filter\IsAbstractFilter;
use Componenta\ClassFinder\Filter\IsFinalFilter;
use Componenta\ClassFinder\Filter\CallableFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\AbstractSample;
use Componenta\ClassFinder\Tests\Fixture\ConcreteClass;
use Componenta\ClassFinder\Tests\Fixture\FinalClass;
use Componenta\ClassFinder\Tests\Fixture\CallableClass;
use Componenta\ClassFinder\Tests\Fixture\NonCallableClass;
use Componenta\ClassFinder\Tests\Fixture\SampleInterface;
use Componenta\ClassFinder\Tests\Fixture\SampleTrait;

describe('InstantiableFilter', function () {
    it('accepts concrete classes', function () {
        $filter = new InstantiableFilter();

        expect($filter->accept(ClassInfoFactory::fromClass(ConcreteClass::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(FinalClass::class)))->toBeTrue();
    });

    it('rejects abstract classes, interfaces, and traits', function () {
        $filter = new InstantiableFilter();

        expect($filter->accept(ClassInfoFactory::fromClass(AbstractSample::class)))->toBeFalse()
            ->and($filter->accept(ClassInfoFactory::fromInterface(SampleInterface::class)))->toBeFalse()
            ->and($filter->accept(ClassInfoFactory::fromTrait(SampleTrait::class)))->toBeFalse();
    });

    it('rejects non-ClassInfo values', function () {
        expect((new InstantiableFilter())->accept('not a ClassInfo'))->toBeFalse();
    });
});

describe('IsAbstractFilter', function () {
    it('accepts abstract classes', function () {
        expect((new IsAbstractFilter())->accept(ClassInfoFactory::fromClass(AbstractSample::class)))->toBeTrue();
    });

    it('rejects concrete classes', function () {
        expect((new IsAbstractFilter())->accept(ClassInfoFactory::fromClass(ConcreteClass::class)))->toBeFalse();
    });

    it('rejects non-ClassInfo values', function () {
        expect((new IsAbstractFilter())->accept('not a ClassInfo'))->toBeFalse();
    });
});

describe('IsFinalFilter', function () {
    it('accepts final classes', function () {
        expect((new IsFinalFilter())->accept(ClassInfoFactory::fromClass(FinalClass::class)))->toBeTrue();
    });

    it('rejects non-final classes', function () {
        expect((new IsFinalFilter())->accept(ClassInfoFactory::fromClass(ConcreteClass::class)))->toBeFalse();
    });

    it('rejects non-ClassInfo values', function () {
        expect((new IsFinalFilter())->accept('not a ClassInfo'))->toBeFalse();
    });
});

describe('CallableFilter', function () {
    it('accepts classes with __invoke', function () {
        expect((new CallableFilter())->accept(ClassInfoFactory::fromClass(CallableClass::class)))->toBeTrue();
    });

    it('rejects classes without __invoke', function () {
        expect((new CallableFilter())->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
    });

    it('rejects non-ClassInfo values', function () {
        expect((new CallableFilter())->accept('not a ClassInfo'))->toBeFalse()
            ->and((new CallableFilter())->accept(null))->toBeFalse();
    });
});
