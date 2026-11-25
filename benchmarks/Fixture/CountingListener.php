<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Benchmarks;

use Componenta\ClassFinder\FinalizableListenerInterface;
use Componenta\Tokenizer\ClassInfo;

final class CountingListener implements FinalizableListenerInterface
{
    public int $handled = 0;
    public int $finalized = 0;

    public function handle(ClassInfo $info): void
    {
        ++$this->handled;
    }

    public function finalize(): void
    {
        ++$this->finalized;
    }
}

