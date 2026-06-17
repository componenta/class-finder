<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

use Componenta\ClassFinder\ClassListenerInterface;
use Componenta\Tokenizer\ClassInfo;

final class SimpleListener implements ClassListenerInterface
{
    /** @var ClassInfo[] */
    public array $handled = [];

    public function handle(ClassInfo $info): void
    {
        $this->handled[] = $info;
    }
}
