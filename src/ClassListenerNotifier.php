<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

final readonly class ClassListenerNotifier
{
    public function __construct(
        private ClassListenerProviderInterface $provider,
    ) {}

    /**
     * Notifies all listeners for each discovered class,
     * then calls finalize() on finalizable listeners regardless of result count.
     */
    public function notify(ClassIteratorInterface $classes): void
    {
        $listeners = [...$this->provider->getClassListeners()];

        foreach ($classes as $class) {
            foreach ($listeners as $listener) {
                $listener->handle($class);
            }
        }

        foreach ($listeners as $listener) {
            if ($listener instanceof FinalizableListenerInterface) {
                $listener->finalize();
            }
        }
    }
}
