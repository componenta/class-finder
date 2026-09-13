<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\AttributePatternFilter;
use Componenta\ClassFinder\Filter\AttributeSearchFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\ClassWithAttribute;
use Componenta\ClassFinder\Tests\Fixture\ClassWithMethodAttribute;

/** @param class-string $class */
it('matches combined wildcards through the public attribute filters', function (
    bool $searchFilter,
    string $pattern,
    bool $expected,
    string $class,
    bool $deep,
): void {
    $filter = $searchFilter
        ? new AttributeSearchFilter([$pattern], deepSearch: $deep)
        : new AttributePatternFilter($pattern, deepSearch: $deep);

    expect($filter->accept(ClassInfoFactory::fromClass($class)))->toBe($expected);
})->with([
    'search filter' => true,
    'pattern filter' => false,
])->with([
    'prefix' => ['Componenta\\ClassFinder\\Tests\\Fixture\\Sample?*', true],
    'suffix' => ['*?Attribute', true],
    'missing attribute' => ['*?MissingAttribute', false],
    'character class without wildcards' => ['Componenta\\ClassFinder\\Tests\\Fixture\\SampleAttribut[e]', true],
    'character range without wildcards' => ['Componenta\\ClassFinder\\Tests\\Fixture\\SampleAttribut[a-z]', true],
    'excluded character without wildcards' => ['Componenta\\ClassFinder\\Tests\\Fixture\\SampleAttribut[!e]', false],
])->with([
    'class attribute' => [ClassWithAttribute::class, false],
    'member attribute' => [ClassWithMethodAttribute::class, true],
]);
