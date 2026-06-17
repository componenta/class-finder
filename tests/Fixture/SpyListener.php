<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

use Componenta\ClassFinder\FinalizableListenerInterface;
use Componenta\Tokenizer\ClassInfo;

final class SpyListener implements FinalizableListenerInterface
{
    /** @var ClassInfo[] */
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
}
