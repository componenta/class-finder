<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\Filter\PatternFilter;

it('reconstructs ordered declarations from a map without loading classes or source files', function (): void {
    $classes = ClassIterator::fromMap([
        ['file' => '/unloaded/Types.php', 'name' => 'PreparedMap\\Base', 'type' => 'class', 'abstract' => true, 'final' => false, 'readonly' => false],
        ['file' => '/unloaded/Types.php', 'name' => 'PreparedMap\\Item', 'type' => 'class', 'abstract' => false, 'final' => true, 'readonly' => true],
        ['file' => '/unloaded/Contract.php', 'name' => 'PreparedMap\\Contract', 'type' => 'interface', 'abstract' => false, 'final' => false, 'readonly' => false],
        ['file' => '/unloaded/Types.php', 'name' => 'PreparedMap\\Behavior', 'type' => 'trait', 'abstract' => false, 'final' => false, 'readonly' => false],
        ['file' => '/unloaded/Types.php', 'name' => 'PreparedMap\\Status', 'type' => 'enum', 'abstract' => false, 'final' => false, 'readonly' => false],
    ]);
    $rows = static function (ClassIterator $iterator): array {
        $rows = [];
        foreach ($iterator as $file => $class) {
            $rows[] = [$file, $class->fullyQualifiedName, $class->type->value, $class->isAbstract, $class->isFinal, $class->isReadonly];
        }
        return $rows;
    };
    $expected = [
        ['/unloaded/Types.php', 'PreparedMap\\Base', 'class', true, false, false],
        ['/unloaded/Types.php', 'PreparedMap\\Item', 'class', false, true, true],
        ['/unloaded/Contract.php', 'PreparedMap\\Contract', 'interface', false, false, false],
        ['/unloaded/Types.php', 'PreparedMap\\Behavior', 'trait', false, false, false],
        ['/unloaded/Types.php', 'PreparedMap\\Status', 'enum', false, false, false],
    ];

    expect($rows($classes))->toBe($expected)
        ->and($rows($classes))->toBe($expected)
        ->and($classes->count())->toBe(5)
        ->and($rows($classes->withFilter(new PatternFilter('Item'))))->toBe([
            ['/unloaded/Types.php', 'PreparedMap\\Item', 'class', false, true, true],
        ])
        ->and($rows($classes))->toBe($expected);
});

it('accepts an empty declaration map', function (): void {
    $classes = ClassIterator::fromMap([]);

    expect($classes->toArray())->toBe([])->and($classes->count())->toBe(0);
});

it('rejects malformed class maps before returning an iterator', function (array $map): void {
    expect(fn () => ClassIterator::fromMap($map))->toThrow(RuntimeException::class);
})->with([
    'named map' => [['named' => []]],
    'sparse list' => [[1 => []]],
    'non-entry' => [[null]],
    'missing metadata' => [[['file' => '/one.php', 'name' => 'One']]],
    'unknown declaration' => [[['file' => '/one.php', 'name' => 'One', 'type' => 'function', 'abstract' => false, 'final' => false, 'readonly' => false]]],
    'invalid modifier' => [[['file' => '/one.php', 'name' => 'One', 'type' => 'class', 'abstract' => 'yes', 'final' => false, 'readonly' => false]]],
]);

it('validates later records eagerly and identifies the invalid declaration', function (string $field, mixed $value): void {
    $valid = ['file' => '/one.php', 'name' => 'One', 'type' => 'class', 'abstract' => false, 'final' => false, 'readonly' => false];
    $invalid = array_replace($valid, [$field => $value]);

    expect(fn () => ClassIterator::fromMap([$valid, $invalid]))
        ->toThrow(RuntimeException::class, 'Invalid discovery map declaration at index 1.');
})->with([
    ['file', ''],
    ['file', 42],
    ['name', ''],
    ['name', null],
    ['type', []],
    ['type', 'unknown'],
    ['abstract', 1],
    ['final', 'false'],
    ['readonly', null],
]);
