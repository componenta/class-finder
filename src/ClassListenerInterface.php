<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Tokenizer\ClassInfo;

interface ClassListenerInterface
{
    public function handle(ClassInfo $info): void;
}