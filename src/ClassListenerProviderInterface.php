<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

interface ClassListenerProviderInterface
{
    /** @return iterable<ClassListenerInterface> */
    public function getClassListeners(): iterable;
}