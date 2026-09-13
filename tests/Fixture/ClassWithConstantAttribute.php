<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

final class ClassWithConstantAttribute
{
    #[SampleAttribute]
    public const string VALUE = 'marked';
}
