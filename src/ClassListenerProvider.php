<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

final class ClassListenerProvider implements ClassListenerProviderInterface
{
    /** @var list<ClassListenerInterface> */
    private array $listeners = [];

    /** @param iterable<ClassListenerInterface> $listeners */
    public function __construct(iterable $listeners = [])
    {
        foreach ($listeners as $listener) {
            $this->addListener($listener);
        }
    }

    public function addListener(ClassListenerInterface $listener): void
    {
        $this->listeners[] = $listener;
    }

    /** @return list<ClassListenerInterface> */
    public function getClassListeners(): array
    {
        return $this->listeners;
    }
}