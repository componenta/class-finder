<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\ClassFinder\Filter\InstantiableFilter;
use Componenta\Tokenizer\TokenizerInterface;

beforeEach(function () {
    $this->scanDir = __DIR__ . '/Fixture/ScanTarget';
});

it('discovers all PHP declarations in a directory', function () {
    $finder = new ClassFinder();

    $names = array_map(fn($info) => $info->name, $finder->find($this->scanDir)->toArray());

    expect($names)
        ->toContain('ScannedClass')
        ->toContain('ScannedInterface')
        ->toContain('ScannedTrait')
        ->toContain('ScannedEnum')
        ->toContain('ScannedAbstract');
});

it('filters declaration types by search mode', function () {
    $finder = new ClassFinder();

    $names = array_map(
        fn($info) => $info->name,
        $finder->find($this->scanDir, [], TokenizerInterface::SEARCH_CLASSES)->toArray(),
    );

    expect($names)
        ->toContain('ScannedClass')
        ->toContain('ScannedAbstract')
        ->not->toContain('ScannedInterface')
        ->not->toContain('ScannedTrait')
        ->not->toContain('ScannedEnum');
});

it('applies constructor filters to results', function () {
    $finder = new ClassFinder(new PatternFilter('*Abstract'));

    $names = array_map(fn($info) => $info->name, $finder->find($this->scanDir)->toArray());

    expect($names)->toBe(['ScannedAbstract']);
});

it('applies multiple filters with AND logic', function () {
    $finder = new ClassFinder([
        new PatternFilter('Scanned*'),
        new InstantiableFilter(),
    ]);

    $names = array_map(fn($info) => $info->name, $finder->find($this->scanDir)->toArray());

    expect($names)
        ->toContain('ScannedClass')
        ->not->toContain('ScannedAbstract')
        ->not->toContain('ScannedInterface');
});

it('excludes directories from scanning', function () {
    $finder = new ClassFinder();

    $names = array_map(
        fn($info) => $info->name,
        $finder->find(__DIR__ . '/Fixture', ['ScanTarget'])->toArray(),
    );

    expect($names)
        ->not->toContain('ScannedClass')
        ->toContain('UserController');
});

it('accepts an array of directories', function () {
    $finder = new ClassFinder();

    expect($finder->find([$this->scanDir])->count())->toBe(5);
});

it('throws on invalid filter in constructor', function () {
    new ClassFinder(['not a filter']);
})->throws(InvalidArgumentException::class, 'must implement FilterInterface');
