<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture\ScanTarget;

enum ScannedEnum: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
