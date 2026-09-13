<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\AnyAttributeFilter;
use Componenta\ClassFinder\Filter\AttributeSearchFilter;
use Componenta\ClassFinder\Tests\Fixture\AnotherAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\ClassWithAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassWithConstantAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassWithMethodAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassWithPropertyAttribute;
use Componenta\ClassFinder\Tests\Fixture\NonCallableClass;
use Componenta\ClassFinder\Tests\Fixture\SampleAttribute;

it('matches attribute names without case sensitivity at every search level', function (
    string $kind,
    string $class,
    string $attribute,
    bool $deep,
): void {
    $name = strtolower($attribute);
    $filter = match ($kind) {
        'single' => AttributeSearchFilter::hasAttribute($name, $deep),
        'all' => AttributeSearchFilter::hasAllAttributes([$name, $attribute], $deep),
        'any' => new AnyAttributeFilter([$name], $deep),
        default => throw new LogicException('Unexpected filter kind.'),
    };

    expect($filter->accept(ClassInfoFactory::fromClass($class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
})->with(['single', 'all', 'any'])->with([
    'class' => [ClassWithAttribute::class, SampleAttribute::class, false],
    'method' => [ClassWithMethodAttribute::class, SampleAttribute::class, true],
    'property' => [ClassWithPropertyAttribute::class, AnotherAttribute::class, true],
    'constant' => [ClassWithConstantAttribute::class, SampleAttribute::class, true],
]);
