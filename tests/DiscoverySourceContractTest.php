<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\Tokenizer\ClassInfo;
use Componenta\Tokenizer\TokenizerInterface;

it('rejects invalid source entries consistently across filtered and unfiltered reads', function (string $invalid): void {
    $classes = new ClassIterator((static function () use ($invalid): Generator {
        yield 'valid.php' => new ClassInfo('Valid');

        if ($invalid === 'key') {
            yield new stdClass() => new ClassInfo('InvalidKey');
        } else {
            yield 'invalid.php' => new stdClass();
        }
    })());
    $filtered = $classes->withFilter(PatternFilter::exactMatch('Valid'));

    foreach ([
        static fn () => count($classes),
        static fn () => $classes->toArray(),
        static fn () => iterator_to_array($classes, false),
        static fn () => count($filtered),
        static fn () => $filtered->toArray(),
    ] as $read) {
        expect($read)->toThrow(UnexpectedValueException::class);
    }
})->with(['key', 'value']);

it('rejects invalid metadata from a direct array source', function (): void {
    expect(fn () => new ClassIterator(['invalid.php' => null])->toArray())
        ->toThrow(UnexpectedValueException::class);
});

it('retains supported keys and the current position of an advanced generator', function (): void {
    $source = (static function (): Generator {
        yield 'skipped.php' => new ClassInfo('Skipped');
        yield null => new ClassInfo('NullKey');
        yield 3 => new ClassInfo('NumericKey');
        yield 'last.php' => new ClassInfo('Last');
    })();
    $source->next();
    $classes = new ClassIterator($source);
    $expected = [[null, 'NullKey'], [3, 'NumericKey'], ['last.php', 'Last']];

    expect(count($classes))->toBe(3)
        ->and(array_map(static fn ($info) => $info->name, $classes->toArray()))
        ->toBe(['NullKey', 'NumericKey', 'Last']);

    $pairs = [];
    foreach ($classes as $key => $info) {
        $pairs[] = [$key, $info->name];
    }

    expect($pairs)->toBe($expected);
});

it('fails discovery consistently when a custom tokenizer returns invalid metadata', function (): void {
    $tokenizer = new class implements TokenizerInterface {
        public function parse(string $source, int $mode = self::SEARCH_ALL): array
        {
            return [new ClassInfo('Valid'), new stdClass()];
        }
    };
    $classes = new ClassFinder(tokenizer: $tokenizer)->find(__DIR__ . '/Fixture/ScanTarget');
    $first = null;

    foreach ([
        static fn () => $classes->toArray(),
        static fn () => count($classes),
        static fn () => iterator_to_array($classes, false),
    ] as $read) {
        try {
            $read();
            throw new RuntimeException('Invalid declaration was accepted.');
        } catch (UnexpectedValueException $error) {
            $first ??= $error;
            expect($error)->toBe($first);
        }
    }
});
