<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

class CallableClass
{
    public function __invoke(): string
    {
        return 'invoked';
    }
}
