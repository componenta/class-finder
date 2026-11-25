<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;

it('replays the validated map even when the caller mutates referenced fields', function (): void {
    $name = 'Demo\\Original';
    $type = 'class';
    $file = '/original.php';
    $abstract = false;
    $map = [['file' => &$file, 'name' => &$name, 'type' => &$type, 'abstract' => &$abstract, 'final' => false, 'readonly' => false]];
    $classes = ClassIterator::fromMap($map);

    $name = '';
    $type = 'invalid';
    $file = '/changed.php';
    $abstract = true;

    $cursor = $classes->getIterator();
    expect($cursor->key())->toBe('/original.php')
        ->and($cursor->current()->fullyQualifiedName)->toBe('Demo\\Original')
        ->and($cursor->current()->isAbstract)->toBeFalse()
        ->and(count($classes))->toBe(1);
});
