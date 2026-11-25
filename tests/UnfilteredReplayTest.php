<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\Tokenizer\ClassInfo;

it('counts and materializes unfiltered declarations without losing duplicate filenames or moving a cursor', function (): void {
    $first = new ClassInfo('First');
    $second = new ClassInfo('Second');
    $reads = 0;
    $source = (static function () use ($first, $second, &$reads): Generator {
        ++$reads;
        yield 'both.php' => $first;
        ++$reads;
        yield 'both.php' => $second;
    })();
    $classes = new ClassIterator($source);
    $cursor = $classes->getIterator();

    expect($cursor->current())->toBe($first)
        ->and(count($classes))->toBe(2)
        ->and($classes->toArray())->toBe([$first, $second])
        ->and($cursor->current())->toBe($first);
    $cursor->next();
    expect($cursor->current())->toBe($second)
        ->and($cursor->key())->toBe('both.php')
        ->and(count($classes))->toBe(2)
        ->and($reads)->toBe(2);

    $filter = PatternFilter::exactMatch('Second');
    $filtered = $classes->withFilter($filter);
    expect(count($filtered))->toBe(1)
        ->and($filtered->toArray())->toBe([$second])
        ->and($filtered->withoutFilter($filter)->toArray())->toBe([$first, $second]);
});

it('does not convert an unfiltered failing source into a successful partial count or array', function (): void {
    $failure = new RuntimeException('Unfiltered source failed.');
    $classes = new ClassIterator((static function () use ($failure): Generator {
        yield 'first.php' => new ClassInfo('First');
        throw $failure;
    })());

    expect(fn () => count($classes))->toThrow($failure)
        ->and(fn () => $classes->toArray())->toThrow($failure)
        ->and(fn () => count($classes))->toThrow($failure);
});
