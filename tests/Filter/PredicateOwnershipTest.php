<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\AnyAttributeFilter;
use Componenta\ClassFinder\Filter\ImplementsAnyFilter;
use Componenta\ClassFinder\Filter\ImplementsFilter;
use Componenta\Tokenizer\ClassInfo;

interface OwnershipPredicateContract {}
final class OwnershipPredicateFixture implements OwnershipPredicateContract {}

it('captures the interface names when creating a predicate', function (bool $any): void {
    $name = OwnershipPredicateContract::class;
    $filter = $any ? new ImplementsAnyFilter([&$name]) : new ImplementsFilter([&$name]);
    $info = new ClassInfo(OwnershipPredicateFixture::class);
    expect($filter->accept($info))->toBeTrue();

    $name = 'NonexistentOwnershipPredicateContract';

    expect($filter->accept($info))->toBeTrue();
})->with([false, true]);

it('rejects an empty any-of selection without requiring reflection or autoloading', function (): void {
    $info = new ClassInfo('NonexistentOwnershipPredicateFixture');

    expect(new ImplementsAnyFilter([])->accept($info))->toBeFalse()
        ->and(new AnyAttributeFilter([])->accept($info))->toBeFalse();
});
