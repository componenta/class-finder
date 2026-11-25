<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\ClassListenerNotifier;
use Componenta\ClassFinder\ClassListenerProvider;
use Componenta\ClassFinder\Tests\Fixture\SpyListener;
use Componenta\Tokenizer\ClassInfo;
use Componenta\Tokenizer\TokenizerInterface;

it('does not silently complete or finalize after a tokenizer fails during discovery', function (): void {
    $failure = new RuntimeException('tokenizer failed');
    $tokenizer = new class($failure) implements TokenizerInterface {
        private int $calls = 0;
        public function __construct(private RuntimeException $failure) {}
        public function parse(string $source, int $mode = self::SEARCH_ALL): array
        {
            if (++$this->calls === 1) {
                return [new ClassInfo('First')];
            }

            throw $this->failure;
        }
    };
    $classes = new ClassFinder(tokenizer: $tokenizer)->find(__DIR__ . '/Fixture/ScanTarget');
    $listener = new SpyListener();
    $notifier = new ClassListenerNotifier(new ClassListenerProvider([$listener]));

    expect(fn () => $notifier->notify($classes))->toThrow($failure)
        ->and($listener->handled)->toHaveCount(1)
        ->and($listener->finalized)->toBeFalse()
        ->and(fn () => $classes->toArray())->toThrow($failure)
        ->and(fn () => count($classes))->toThrow($failure);
});
