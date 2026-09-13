<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\ClassFinder\Tests\Fixture\UserController;
use Componenta\ClassFinder\Tests\Fixture\ProductController;
use Componenta\Tokenizer\ClassInfo;

it('keeps outer discovery complete during counting and nested reads', function (string $read, bool $warm): void {
    $reads = 0;
    $classes = new ClassIterator((static function () use (&$reads): Generator {
        ++$reads;
        yield 'first.php' => new ClassInfo(UserController::class);
        ++$reads;
        yield 'second.php' => new ClassInfo(ProductController::class);
    })());
    if ($warm) {
        expect(count($classes))->toBe(2);
    }
    $seen = [];
    $filtered = $classes->withFilter(new PatternFilter('*Controller'));

    foreach ($classes as $file => $info) {
        $seen[$file] = $info->fullyQualifiedName;
        if ($read === 'count') {
            expect(count($classes))->toBe(2);
        } else {
            $inner = $read === 'filtered' ? $filtered : $classes;
            expect(array_map(static fn (ClassInfo $item): string => $item->fullyQualifiedName, $inner->toArray()))
                ->toBe([UserController::class, ProductController::class]);
        }
    }

    expect($seen)->toBe(['first.php' => UserController::class, 'second.php' => ProductController::class])
        ->and($reads)->toBe(2);
})->with(['count', 'nested', 'filtered'])->with([false, true]);
