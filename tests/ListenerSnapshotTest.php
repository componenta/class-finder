<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\ClassListenerNotifier;
use Componenta\ClassFinder\ClassListenerProviderInterface;
use Componenta\ClassFinder\Tests\Fixture\SpyListener;
use Componenta\Tokenizer\ClassInfo;

it('notifies and finalizes every listener even when provider keys repeat', function (): void {
    $first = new SpyListener();
    $second = new SpyListener();
    $provider = new class($first, $second) implements ClassListenerProviderInterface {
        public function __construct(private SpyListener $first, private SpyListener $second) {}
        public function getClassListeners(): iterable
        {
            yield 'same' => $this->first;
            yield 'same' => $this->second;
        }
    };
    $info = new ClassInfo('Discovered');

    new ClassListenerNotifier($provider)->notify(new ClassIterator(['file.php' => $info]));

    expect($first->handled)->toBe([$info])
        ->and($second->handled)->toBe([$info])
        ->and($first->finalized)->toBeTrue()
        ->and($second->finalized)->toBeTrue();
});
