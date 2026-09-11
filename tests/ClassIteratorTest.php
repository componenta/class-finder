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
use Componenta\Filter\PredicateInterface;

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

it('accepts a predicate that has no collection-filter behavior', function () {
    $predicate = new class implements PredicateInterface {
        public function accept(mixed $value, string|int|null $key = null): bool
        {
            return $value->name === 'UserController';
        }
    };

    $iterator = new ClassIterator($this->items, $predicate);

    expect(array_map(
        static fn($info): string => $info->name,
        $iterator->toArray(),
    ))->toBe(['UserController']);
});

it('applies multiple filters with AND logic', function () {
    $items = [...$this->items, ClassInfoFactory::fromClass(AbstractSample::class)];

    $iterator = new ClassIterator($items, [
        new PatternFilter('*Sample*'),
        new InstantiableFilter(),
    ]);

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

it('keeps immutable clones independent during interleaved iteration', function () {
    $iterator = new ClassIterator($this->items);
    $filtered = $iterator->withFilter(new PatternFilter('*'));

    $originalCursor = $iterator->getIterator();
    $filteredCursor = $filtered->getIterator();

    expect($originalCursor->current()->name)->toBe('UserController')
        ->and($filteredCursor->current()->name)->toBe('UserController');

    $originalCursor->next();
    expect($originalCursor->current()->name)->toBe('ProductController');

    $filteredCursor->next();
    expect($filteredCursor->current()->name)->toBe('ProductController');

    $originalCursor->next();
    expect($originalCursor->current()->name)->toBe('UserService');

    $filteredCursor->next();
    expect($filteredCursor->current()->name)->toBe('UserService');
});

it('throws on invalid filter in constructor', function () {
    new ClassIterator([], ['not a filter']);
})->throws(InvalidArgumentException::class, 'must implement PredicateInterface');
