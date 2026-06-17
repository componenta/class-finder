<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

#[SampleAttribute]
class ClassWithPropertyAttribute
{
    #[AnotherAttribute]
    public string $annotatedProperty = '';
}
