<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\ClassFinder\Filter\InstantiableFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\UserController;
use Componenta\ClassFinder\Tests\Fixture\ProductController;
use Componenta\ClassFinder\Tests\Fixture\UserService;
use Componenta\ClassFinder\Tests\Fixture\AbstractSample;

beforeEach(function () {
    $this->items = [
        ClassInfoFactory::fromClass(UserController::class),
        ClassInfoFactory::fromClass(ProductController::class),
        ClassInfoFactory::fromClass(UserService::class),
    ];
});

it('yields all items without filters', function () {
    $iterator = new ClassIterator($this->items);

    expect(iterator_to_array($iterator))->toHaveCount(3);
});

it('applies filter during iteration', function () {
    $iterator = new ClassIterator($this->items, new PatternFilter('*Controller'));

    expect(iterator_to_array($iterator))->toHaveCount(2);
});

it('applies multiple filters with AND logic', function () {
    $items = [...$this->items, ClassInfoFactory::fromClass(AbstractSample::class)];

    $iterator = new ClassIterator($items, [
        new PatternFilter('*Sample*'),
        new InstantiableFilter(),
    ]);

    // AbstractSample matches *Sample* but is rejected by InstantiableFilter (abstract)
    // No other item matches *Sample*, so result is empty
    expect(iterator_to_array($iterator))->toHaveCount(0);
});

it('returns filtered items via toArray', function () {
    $iterator = new ClassIterator($this->items, new PatternFilter('*Controller'));

    $result = $iterator->toArray();

    expect($result)->toHaveCount(2)
        ->and($result[0]->name)->toBe('UserController')
        ->and($result[1]->name)->toBe('ProductController');
});

it('returns filtered count', function () {
    $iterator = new ClassIterator($this->items, new PatternFilter('*Controller'));

    expect($iterator->count())->toBe(2);
});

it('can be traversed multiple times', function () {
    $iterator = new ClassIterator($this->items);

    $first = iterator_to_array($iterator);
    $second = iterator_to_array($iterator);

    expect($first)->toHaveCount(3)
        ->and($second)->toHaveCount(3);
});

it('accepts a generator as input', function () {
    $generator = (function () {
        yield ClassInfoFactory::fromClass(UserController::class);
        yield ClassInfoFactory::fromClass(ProductController::class);
    })();

    $iterator = new ClassIterator($generator);

    expect(iterator_to_array($iterator))->toHaveCount(2);
});

it('returns a new instance with added filter via withFilter', function () {
    $iterator = new ClassIterator($this->items);
    $filtered = $iterator->withFilter(new PatternFilter('*Controller'));

    expect($filtered)->not->toBe($iterator)
        ->and(iterator_to_array($iterator))->toHaveCount(3)
        ->and(iterator_to_array($filtered))->toHaveCount(2);
});

it('returns a new instance with removed filter via withoutFilter', function () {
    $filter = new PatternFilter('*Controller');
    $iterator = new ClassIterator($this->items, $filter);
    $unfiltered = $iterator->withoutFilter($filter);

    expect($unfiltered)->not->toBe($iterator)
        ->and(iterator_to_array($iterator))->toHaveCount(2)
        ->and(iterator_to_array($unfiltered))->toHaveCount(3);
});

it('throws on invalid filter in constructor', function () {
    new ClassIterator([], ['not a filter']);
})->throws(InvalidArgumentException::class, 'must implement FilterInterface');
