<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

#[SampleAttribute]
class ClassWithAttribute
{
    #[SampleAttribute]
    public function annotatedMethod(): void {}
}
