<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\AnyAttributeFilter;
use Componenta\ClassFinder\Filter\AttributePatternFilter;
use Componenta\ClassFinder\Filter\AttributeSearchFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\ClassWithAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassWithMethodAttribute;
use Componenta\ClassFinder\Tests\Fixture\NonCallableClass;
use Componenta\ClassFinder\Tests\Fixture\SampleAttribute;

it('treats exact attribute names consistently across public filter APIs', function (string $kind, string $class, bool $deep): void {
    $name = '\\' . strtolower(SampleAttribute::class);
    $filter = match ($kind) {
        'pattern' => AttributePatternFilter::exactAttribute($name, $deep),
        'search' => AttributeSearchFilter::hasAttribute($name, $deep),
        'any' => new AnyAttributeFilter([$name], $deep),
    };

    expect($filter->accept(ClassInfoFactory::fromClass($class)))->toBeTrue()
        ->and($filter->accept(ClassInfoFactory::fromClass(NonCallableClass::class)))->toBeFalse();
})->with(['pattern', 'search', 'any'])->with([
    [ClassWithAttribute::class, false],
    [ClassWithMethodAttribute::class, true],
]);
