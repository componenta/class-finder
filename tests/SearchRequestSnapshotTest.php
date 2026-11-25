<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinder;

it('retains the requested roots and exclusions when caller references change before traversal', function (): void {
    $root = __DIR__ . '/Fixture';
    $excluded = 'ScanTarget';
    $roots = [&$root];
    $exclusions = [&$excluded];

    $classes = new ClassFinder()->find($roots, $exclusions);
    $root = __DIR__ . '/Fixture/ScanTarget';
    $excluded = 'OtherDirectory';

    $names = array_map(static fn ($info) => $info->name, $classes->toArray());

    expect($names)->toContain('UserController')->not->toContain('ScannedClass');
});

it('retains excluded directories independently of caller references', function (): void {
    $excluded = 'ScanTarget';
    $classes = new ClassFinder()->find(__DIR__ . '/Fixture', [&$excluded]);
    $excluded = 'OtherDirectory';

    $names = array_map(static fn ($info) => $info->name, $classes->toArray());

    expect($names)->toContain('UserController')->not->toContain('ScannedClass');
});
