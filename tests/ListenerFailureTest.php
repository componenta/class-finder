<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\ClassListenerNotifier;
use Componenta\ClassFinder\ClassListenerProvider;
use Componenta\ClassFinder\FinalizableListenerInterface;
use Componenta\ClassFinder\Tests\Fixture\SpyListener;
use Componenta\Tokenizer\ClassInfo;

it('does not finalize an unsuccessful listener traversal', function (): void {
    $failure = new RuntimeException('handle failed');
    $broken = new class($failure) implements FinalizableListenerInterface {
        public bool $finalized = false;
        public function __construct(private RuntimeException $failure) {}
        public function handle(ClassInfo $info): void { throw $this->failure; }
        public function finalize(): void { $this->finalized = true; }
    };
    $other = new SpyListener();
    $notifier = new ClassListenerNotifier(new ClassListenerProvider([$broken, $other]));

    expect(fn () => $notifier->notify(new ClassIterator(['file.php' => new ClassInfo('First')])))->toThrow($failure)
        ->and($broken->finalized)->toBeFalse()
        ->and($other->handled)->toBe([])
        ->and($other->finalized)->toBeFalse();
});

it('propagates a finalization failure without continuing to later finalizers', function (): void {
    $failure = new RuntimeException('finalize failed');
    $broken = new class($failure) implements FinalizableListenerInterface {
        public function __construct(private RuntimeException $failure) {}
        public function handle(ClassInfo $info): void {}
        public function finalize(): void { throw $this->failure; }
    };
    $other = new SpyListener();
    $notifier = new ClassListenerNotifier(new ClassListenerProvider([$broken, $other]));
    $info = new ClassInfo('First');

    expect(fn () => $notifier->notify(new ClassIterator(['file.php' => $info])))->toThrow($failure)
        ->and($other->handled)->toBe([$info])
        ->and($other->finalized)->toBeFalse();
});
