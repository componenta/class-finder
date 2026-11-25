<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\ClassListenerNotifier;
use Componenta\ClassFinder\ClassListenerProviderInterface;
use Componenta\ClassFinder\FinalizableListenerInterface;
use Componenta\Tokenizer\ClassInfo;

it('handles and finalizes each listener instance once per discovery even with duplicate registrations', function (bool $empty): void {
    $newListener = static fn () => new class implements FinalizableListenerInterface {
        public array $names = [];
        public int $finalizations = 0;
        public function handle(ClassInfo $info): void { $this->names[] = $info->fullyQualifiedName; }
        public function finalize(): void { ++$this->finalizations; }
    };
    $first = $newListener();
    $second = $newListener();
    $provider = new class($first, $second) implements ClassListenerProviderInterface {
        public function __construct(private FinalizableListenerInterface $first, private FinalizableListenerInterface $second) {}
        public function getClassListeners(): iterable
        {
            yield 'one' => $this->first;
            yield 'two' => $this->first;
            yield 'one' => $this->second;
            yield $this->second;
        }
    };
    $entries = $empty ? [] : ['one.php' => new ClassInfo('One'), 'two.php' => new ClassInfo('Two')];

    new ClassListenerNotifier($provider)->notify(new ClassIterator($entries));

    $expected = $empty ? [] : ['One', 'Two'];
    expect($first->names)->toBe($expected)
        ->and($second->names)->toBe($expected)
        ->and($first->finalizations)->toBe(1)
        ->and($second->finalizations)->toBe(1);
})->with([false, true]);
