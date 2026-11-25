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
     * Source, handling and finalization errors propagate immediately.
     * Finalization is a success callback, not cleanup; a failed traversal is
     * not finalized. Effects already performed by listeners are not rolled back.
     */
    public function notify(ClassIteratorInterface $classes): void
    {
        $listeners = [];
        foreach ($this->provider->getClassListeners() as $listener) {
            if (!$listener instanceof ClassListenerInterface) {
                throw new \UnexpectedValueException('Listener provider must yield ClassListenerInterface instances.');
            }

            $listeners[] = $listener;
        }

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
