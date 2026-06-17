<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Attribute;

use Attribute;

/**
 * Marks a listener as development-only.
 * Skipped during production cache restore - data comes from other sources in prod.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class DevOnly
{
}
