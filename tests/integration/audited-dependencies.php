<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\Stdlib\ReplayableIterator;
use Componenta\Tokenizer\Tokenizer;
use Composer\InstalledVersions;

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};

foreach ([
    'componenta/tokenizer' => Tokenizer::class,
    'componenta/iterator' => ReplayableIterator::class,
] as $package => $class) {
    $root = realpath(InstalledVersions::getInstallPath($package));
    $file = realpath((new ReflectionClass($class))->getFileName());
    $check($root !== false && $file !== false && str_starts_with($file, $root . DIRECTORY_SEPARATOR), 'Unexpected source: ' . $class);
}

$dir = sys_get_temp_dir() . '/discovery-integration-' . bin2hex(random_bytes(12));
if (!mkdir($dir, 0700)) {
    throw new RuntimeException('Cannot create test directory.');
}
$file = $dir . '/Declarations.php';

try {
    $source = <<<'PHP'
    <?php
    namespace /* comment */ Integration;
    $value = 1;
    $closure = function () use ($value) {
        class CapturedController {}
    };
    class DirectController {}
    class Service {}
    PHP;
    $check(file_put_contents($file, $source) !== false, 'Cannot write test fixture.');
    $classes = new ClassFinder()->find($dir);
    $filtered = $classes->withFilter(PatternFilter::endsWith('Controller'));
    $first = $classes->getIterator();
    $check($first->current()->fullyQualifiedName === 'Integration\\CapturedController', 'Closure declaration missing.');
    $check(count($filtered) === 2, 'Filtered count differs.');
    $first->next();
    $check($first->current()->fullyQualifiedName === 'Integration\\DirectController', 'Interleaved cursor lost its position.');
    $check(array_map(static fn ($info) => $info->fullyQualifiedName, $classes->toArray()) === [
        'Integration\\CapturedController',
        'Integration\\DirectController',
        'Integration\\Service',
    ], 'Discovery lost or duplicated declarations.');
} finally {
    if (is_file($file)) {
        unlink($file);
    }
    rmdir($dir);
}

$iterator = new ReplayableIterator((static function (): Generator {
    yield null => 1;
    yield '' => 2;
})());
$check($iterator->cursor()->current() === 1, 'Unexpected first replay entry.');
try {
    clone $iterator;
    throw new RuntimeException('Active source clone unexpectedly succeeded.');
} catch (LogicException $exception) {
    $check(str_contains($exception->getMessage(), 'Cannot clone an active replayable iterator'), 'Wrong clone failure.');
}
$check($iterator->toArray(true) === ['' => 2], 'Unexpected key normalization.');
$copy = clone $iterator;
$copy->next();
$check($iterator->current() === 1 && $copy->current() === 2, 'Completed clone shares cursor state.');

echo "Audited dependency integration passed.\n";
