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

    $check(file_put_contents($file, '<?php namespace readonly; class KeywordController {}') !== false, 'Cannot write keyword namespace fixture.');
    $found = new ClassFinder()->find($dir)->withFilter(PatternFilter::exactNamespace('readonly'))->toArray();
    $check(count($found) === 1 && $found[0]->fullyQualifiedName === 'readonly\\KeywordController', 'Keyword namespace discovery failed.');

    $tagSource = '<?php namespace TagIntegration ?><?php use Vendor\\Dependency ?>HTML<?php final readonly class TagController {} class OtherService {}';
    $check(file_put_contents($file, $tagSource) !== false, 'Cannot write PHP tag fixture.');
    $classes = new ClassFinder()->find($dir);
    $found = $classes->withFilter(PatternFilter::endsWith('Controller'))->toArray();
    $check(count($classes) === 2 && count($found) === 1, 'Implicit tag termination lost declarations.');
    $check(
        $found[0]->fullyQualifiedName === 'TagIntegration\\TagController'
        && $found[0]->isFinal
        && $found[0]->isReadonly,
        'Implicit tag termination changed declaration metadata.',
    );

    if (PHP_VERSION_ID >= 80500) {
        $callbackSource = <<<'PHP'
        <?php
        namespace Integration;
        #[Callback(static function (): void {
            class AttributeController {}
        })]
        function decorated(): void {}
        class DirectController {}
        PHP;
        $check(file_put_contents($file, $callbackSource) !== false, 'Cannot write callback fixture.');
        $found = new ClassFinder()->find($dir)->withFilter(PatternFilter::endsWith('Controller'))->toArray();
        $check(array_map(static fn ($info) => $info->fullyQualifiedName, $found) === [
            'Integration\\AttributeController',
            'Integration\\DirectController',
        ], 'PHP 8.5 callback declarations were missed by discovery.');
    }
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

$failure = new RuntimeException('Original source failure.');
$failed = new ReplayableIterator(new class($failure) implements Iterator {
    public function __construct(private readonly Throwable $failure) {}
    public function rewind(): void {}
    public function current(): mixed { return null; }
    public function key(): mixed { return null; }
    public function next(): void {}
    public function valid(): bool { throw $this->failure; }
    public function __destruct() { throw new RuntimeException('Cleanup failure.'); }
});
foreach ([static fn () => $failed->current(), static fn () => $failed->toArray()] as $read) {
    $caught = null;
    try {
        $read();
    } catch (Throwable $error) {
        $caught = $error;
    }
    $check($caught === $failure, 'Replay replaced original error.');
}

$info = new \Componenta\Tokenizer\ClassInfo(\stdClass::class);
$exposed = $info->reflector;
$exposed->__construct(\DateTime::class);
$check($info->reflector->getName() === \stdClass::class, 'Reflection metadata ownership violated.');
$check(!(new \Componenta\ClassFinder\Filter\ImplementsFilter(\DateTimeInterface::class))->accept($info), 'Filter accepted a substituted reflected identity.');

$stale = \Componenta\ClassFinder\ClassIterator::fromMap([
    ['file' => '/stale.php', 'name' => \DateTimeInterface::class, 'type' => 'class', 'abstract' => false, 'final' => false, 'readonly' => false],
]);
$check(count($stale) === 1, 'Metadata-only traversal must remain available.');
try {
    $stale->withFilter(new \Componenta\ClassFinder\Filter\ImplementsFilter(\DateTimeInterface::class))->toArray();
    throw new RuntimeException('Stale declaration kind unexpectedly accepted.');
} catch (\ReflectionException) {
    // The map must not silently be interpreted as a different declaration kind.
}

$classes = new \Componenta\ClassFinder\ClassIterator((static function (): Generator {
    yield 'first.php' => new \Componenta\Tokenizer\ClassInfo('First');
    Fiber::suspend('discovery paused');
    yield 'second.php' => new \Componenta\Tokenizer\ClassInfo('Second');
})());
$reader = new Fiber(static fn () => count($classes));
$check($reader->start() === 'discovery paused', 'Expected suspended discovery.');
unset($reader);
gc_collect_cycles();

$filtered = $classes->withFilter(PatternFilter::exactMatch('First'));
$interrupted = null;
foreach ([static fn () => count($classes), static fn () => $filtered->toArray()] as $read) {
    try {
        $read();
        throw new RuntimeException('Interrupted discovery returned a partial success.');
    } catch (LogicException $error) {
        $check($error->getMessage() === 'Source read was interrupted.', 'Unexpected interruption failure.');
        $interrupted ??= $error;
        $check($interrupted === $error, 'Filtered iterator lost the shared failure.');
    }
}

echo "Audited dependency integration passed.\n";
