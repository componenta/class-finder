<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\AttributeSearchFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\ClassWithAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassWithMultipleAttributes;
use Componenta\ClassFinder\Tests\Fixture\ClassWithMethodAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassWithPropertyAttribute;
use Componenta\ClassFinder\Tests\Fixture\NonCallableClass;
use Componenta\ClassFinder\Tests\Fixture\SampleAttribute;
use Componenta\ClassFinder\Tests\Fixture\AnotherAttribute;

describe('hasAttribute (single, OR mode)', function () {
    it('matches a class with the attribute', function () {
        $filter = AttributeSearchFilter::hasAttribute(SampleAttribute::class);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
    });
});

describe('hasAnyAttribute (multiple, OR mode)', function () {
    it('matches a class having any of the attributes', function () {
        $filter = AttributeSearchFilter::hasAnyAttribute([
            SampleAttribute::class,
            AnotherAttribute::class,
        ]);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ClassWithMultipleAttributes::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
    });
});

describe('hasAllAttributes (AND mode)', function () {
    it('requires all attributes to be present', function () {
        $filter = AttributeSearchFilter::hasAllAttributes([
            SampleAttribute::class,
            AnotherAttribute::class,
        ]);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithMultipleAttributes::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeFalse();
    });
});

describe('hasAnyAttributes (presence check)', function () {
    it('accepts classes with any attributes when mustHave is true', function () {
        $filter = AttributeSearchFilter::hasAnyAttributes(mustHaveAttributes: true);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
    });

    it('accepts classes without attributes when mustHave is false', function () {
        $filter = AttributeSearchFilter::hasAnyAttributes(mustHaveAttributes: false);

        expect($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeTrue()
            ->and($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeFalse();
    });
});

describe('deep search', function () {
    it('finds attributes on methods', function () {
        $filter = AttributeSearchFilter::hasAttribute(SampleAttribute::class, deepSearch: true);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithMethodAttribute::class)))->toBeTrue();
    });

    it('ignores method attributes without deep search', function () {
        $filter = AttributeSearchFilter::hasAttribute(SampleAttribute::class, deepSearch: false);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithMethodAttribute::class)))->toBeFalse();
    });

    it('finds all attributes when split between class and members', function () {
        $filter = AttributeSearchFilter::hasAllAttributes(
            [SampleAttribute::class, AnotherAttribute::class],
            deepSearch: true,
        );

        // ClassWithPropertyAttribute: #[SampleAttribute] on class, #[AnotherAttribute] on property
        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithPropertyAttribute::class)))->toBeTrue();
    });

    it('rejects when only one attribute found at both levels (no double-counting)', function () {
        $filter = AttributeSearchFilter::hasAllAttributes(
            [SampleAttribute::class, AnotherAttribute::class],
            deepSearch: true,
        );

        // ClassWithAttribute: #[SampleAttribute] on class + #[SampleAttribute] on method - no AnotherAttribute
        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeFalse();
    });

    it('hasAllAttributes without deep search misses member-only attributes', function () {
        $filter = AttributeSearchFilter::hasAllAttributes(
            [SampleAttribute::class, AnotherAttribute::class],
            deepSearch: false,
        );

        // ClassWithPropertyAttribute has AnotherAttribute on property only - not found without deep search
        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithPropertyAttribute::class)))->toBeFalse();
    });
});

describe('wildcard patterns', function () {
    it('matches attribute name by wildcard', function () {
        $filter = new AttributeSearchFilter(['*Attribute']);

        expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeTrue();
    });
});

it('returns false for empty attributes list', function () {
    $filter = new AttributeSearchFilter([]);

    expect($filter->accept(ClassInfoFactory::fromClass(ClassWithAttribute::class)))->toBeFalse();
});

it('rejects non-ClassInfo values', function () {
    $filter = AttributeSearchFilter::hasAttribute(SampleAttribute::class);

    expect($filter->accept('not a ClassInfo'))->toBeFalse()
        ->and($filter->accept(null))->toBeFalse();
});
