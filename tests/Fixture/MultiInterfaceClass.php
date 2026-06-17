<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

class MultiInterfaceClass implements SampleInterface, AnotherInterface
{
    public function doSomething(): void
    {
    }

    public function doAnother(): void
    {
    }
}
