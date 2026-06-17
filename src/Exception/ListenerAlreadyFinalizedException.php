<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Exception;

use LogicException;

final class ListenerAlreadyFinalizedException extends LogicException implements FinalizationExceptionInterface
{
    public static function forListener(object|string $listener): self
    {
        $class = is_object($listener) ? $listener::class : $listener;

        return new self(sprintf('Listener "%s" is already finalized.', $class));
    }
}
