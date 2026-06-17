<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

class ConcreteClass extends AbstractSample implements SampleInterface
{
    public function abstractMethod(): void
    {
    }

    public function doSomething(): void
    {
    }
}
