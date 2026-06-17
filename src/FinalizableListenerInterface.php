<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\ClassFinder\Exception\FinalizationExceptionInterface;

/**
 * finalize() is called after scanning completes, even if no classes were found.
 *
 * The normal discovery flow finalizes a listener once. Implementations may
 * reject repeated calls by throwing {@see FinalizationExceptionInterface}.
 */
interface FinalizableListenerInterface extends ClassListenerInterface
{
    /**
     * @throws FinalizationExceptionInterface When the listener rejects finalization, including repeated calls.
     */
    public function finalize(): void;
}
