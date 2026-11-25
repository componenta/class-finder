<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinder;

it('scans each canonical file once across overlapping directories', function (): void {
    $dir = sys_get_temp_dir() . '/class-finder-overlap-' . bin2hex(random_bytes(12));
    mkdir($dir . '/nested', 0700, true);
    $file = $dir . '/nested/Multiple.php';
    file_put_contents($file, '<?php namespace OverlapFixture; class A {} class B {}');

    try {
        $entries = [];
        foreach (new ClassFinder()->find([$dir, $dir . '/nested', $dir]) as $path => $info) {
            $entries[] = [$path, $info->fullyQualifiedName];
        }

        expect($entries)->toBe([
            [realpath($file), 'OverlapFixture\\A'],
            [realpath($file), 'OverlapFixture\\B'],
        ]);
    } finally {
        unlink($file);
        rmdir($dir . '/nested');
        rmdir($dir);
    }
});
