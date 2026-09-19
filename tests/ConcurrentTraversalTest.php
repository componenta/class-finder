<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\Tests\Fixture\UserController;
use Componenta\ClassFinder\Tests\Fixture\ProductController;
use Componenta\Tokenizer\ClassInfo;

it('preserves discovered classes after rejecting an overlapping source read', function (): void {
    $source = new class([
        'first.php' => new ClassInfo(UserController::class),
        'second.php' => new ClassInfo(ProductController::class),
    ]) extends ArrayIterator {
        private bool $suspended = false;
        public function valid(): bool {
            if (!$this->suspended && Fiber::getCurrent() !== null) {
                $this->suspended = true;
                Fiber::suspend('discovery read');
            }
            return parent::valid();
        }
    };
    $classes = new ClassIterator($source);
    $reader = new Fiber(static fn () => $classes->getIterator()->current());
    expect($reader->start())->toBe('discovery read');
    try {
        $other = new Fiber(static fn () => $classes->toArray());
        expect(fn () => $other->start())->toThrow(LogicException::class, 'Source read is already in progress');
    } finally {
        $reader->resume();
    }

    expect($reader->getReturn()->fullyQualifiedName)->toBe(UserController::class)
        ->and(array_map(static fn (ClassInfo $info): string => $info->fullyQualifiedName, $classes->toArray()))
        ->toBe([UserController::class, ProductController::class])
        ->and(count($classes))->toBe(2);
});
