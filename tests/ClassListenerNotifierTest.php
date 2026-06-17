<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\ClassListenerNotifier;
use Componenta\ClassFinder\ClassListenerProviderInterface;
use Componenta\ClassFinder\FinalizableListenerInterface;
use Componenta\ClassFinder\ClassListenerProvider;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\SpyListener;
use Componenta\ClassFinder\Tests\Fixture\SimpleListener;
use Componenta\ClassFinder\Tests\Fixture\UserController;
use Componenta\ClassFinder\Tests\Fixture\ProductController;
use Componenta\Tokenizer\ClassInfo;

it('calls handle on all listeners for each class', function () {
    $listener1 = new SpyListener();
    $listener2 = new SpyListener();
    $notifier = new ClassListenerNotifier(new ClassListenerProvider([$listener1, $listener2]));

    $iterator = new ClassIterator([
        ClassInfoFactory::fromClass(UserController::class),
        ClassInfoFactory::fromClass(ProductController::class),
    ]);

    $notifier->notify($iterator);

    expect($listener1->handled)->toHaveCount(2)
        ->and($listener2->handled)->toHaveCount(2);
});

it('calls finalize on finalizable listeners after iteration', function () {
    $listener = new SpyListener();
    $notifier = new ClassListenerNotifier(new ClassListenerProvider([$listener]));

    $notifier->notify(new ClassIterator([
        ClassInfoFactory::fromClass(UserController::class),
    ]));

    expect($listener->finalized)->toBeTrue();
});

it('calls handle on non-finalizable listeners', function () {
    $simple = new SimpleListener();
    $notifier = new ClassListenerNotifier(new ClassListenerProvider([$simple]));

    $notifier->notify(new ClassIterator([
        ClassInfoFactory::fromClass(UserController::class),
    ]));

    expect($simple->handled)->toHaveCount(1);
});

it('calls finalize even when iterator is empty', function () {
    $listener = new SpyListener();
    $notifier = new ClassListenerNotifier(new ClassListenerProvider([$listener]));

    $notifier->notify(new ClassIterator([]));

    expect($listener->handled)->toBeEmpty()
        ->and($listener->finalized)->toBeTrue();
});

it('uses one listener snapshot for handling and finalization', function () {
    $provider = new class implements ClassListenerProviderInterface {
        /** @var list<object> */
        public array $createdListeners = [];

        public function getClassListeners(): iterable
        {
            $listener = new class implements FinalizableListenerInterface {
                /** @var list<ClassInfo> */
                public array $handled = [];
                public bool $finalized = false;

                public function handle(ClassInfo $info): void
                {
                    $this->handled[] = $info;
                }

                public function finalize(): void
                {
                    $this->finalized = true;
                }
            };

            $this->createdListeners[] = $listener;

            return [$listener];
        }
    };
    $notifier = new ClassListenerNotifier($provider);

    $notifier->notify(new ClassIterator([
        ClassInfoFactory::fromClass(UserController::class),
        ClassInfoFactory::fromClass(ProductController::class),
    ]));

    expect($provider->createdListeners)->toHaveCount(1)
        ->and($provider->createdListeners[0]->handled)->toHaveCount(2)
        ->and($provider->createdListeners[0]->finalized)->toBeTrue();
});
