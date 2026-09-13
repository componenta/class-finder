<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\UserController;

it('combines single-character and star wildcards in class patterns', function (string $pattern, bool $matches): void {
    $filter = new PatternFilter($pattern);

    expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBe($matches);
})->with([
    'prefix' => ['User?*', true],
    'suffix' => ['*?Controller', true],
    'contains' => ['*?*', true],
    'missing prefix' => ['Product?*', false],
    'extra required characters' => ['User??Controller', false],
    'missing contained name' => ['*?Product*', false],
    'character class prefix' => ['[UP]ser*', true],
    'negated character class' => ['[!U]ser*', false],
    'character class without a star' => ['UserControlle[r]', true],
]);

it('matches wildcard namespaces without treating their prefixes as literal text', function (string $pattern, bool $matches): void {
    $filter = new PatternFilter($pattern);

    expect($filter->accept(ClassInfoFactory::fromClass(UserController::class)))->toBe($matches);
})->with([
    'single character in namespace' => ['Componenta\\ClassFinder\\Tests\\Fixtur?\\*', true],
    'character class in namespace' => ['Componenta\\ClassFinder\\Tests\\[FP]ixture\\*', true],
    'nonmatching namespace' => ['Componenta\\ClassFinder\\Tests\\Fixtur??\\*', false],
]);
