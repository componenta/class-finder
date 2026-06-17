<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

class ClassWithMethodAttribute
{
    #[SampleAttribute]
    public function annotatedMethod(): void
    {
    }
}
