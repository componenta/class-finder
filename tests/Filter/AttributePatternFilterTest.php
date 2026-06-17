<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\AttributePatternFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\ClassWithAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassWithMultipleAttributes;
use Componenta\ClassFinder\Tests\Fixture\ClassWithMethodAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassWithPropertyAttribute;
use Componenta\ClassFinder\Tests\Fixture\NonCallableClass;
use Componenta\ClassFinder\Tests\Fixture\SampleAttribute;
use Componenta\ClassFinder\Tests\Fixture\AnotherAttribute;

it('matches exact attribute name', function () {
    $filter = new AttributePatternFilter(SampleAttribute::class);

    expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
});

it('matches suffix pattern', function () {
    $filter = new AttributePatternFilter('*Attribute');

    expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
});

it('matches prefix pattern', function () {
    $filter = new AttributePatternFilter('Componenta\\ClassFinder\\Tests\\Fixture\\Sample*');

    expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue();
});

it('matches contains pattern', function () {
    $filter = new AttributePatternFilter('*Attrib*');

    expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue();
});

describe('deep search', function () {
    it('finds attributes on methods', function () {
        $filter = new AttributePatternFilter(SampleAttribute::class, deepSearch: true);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithMethodAttribute::class)))->toBeTrue();
    });

    it('finds attributes on properties', function () {
        $filter = new AttributePatternFilter(AnotherAttribute::class, deepSearch: true);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithPropertyAttribute::class)))->toBeTrue();
    });

    it('ignores member attributes without deep search', function () {
        $filter = new AttributePatternFilter(SampleAttribute::class, deepSearch: false);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithMethodAttribute::class)))->toBeFalse();
    });
});

describe('static factories', function () {
    it('exactAttribute matches exact name', function () {
        $filter = AttributePatternFilter::exactAttribute(SampleAttribute::class);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue();
    });

    it('anyAttribute matches any from list', function () {
        $filter = AttributePatternFilter::anyAttribute([
            SampleAttribute::class,
            AnotherAttribute::class,
        ]);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ClassWithMultipleAttributes::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
    });

    it('attributePrefix matches prefix', function () {
        $filter = AttributePatternFilter::attributePrefix('Componenta\\ClassFinder\\Tests\\Fixture\\Sample');

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue();
    });
});

it('rejects non-ClassInfo values', function () {
    $filter = new AttributePatternFilter(SampleAttribute::class);

    expect($filter->accept('not a ClassInfo'))->toBeFalse()
        ->and($filter->accept(null))->toBeFalse();
});
