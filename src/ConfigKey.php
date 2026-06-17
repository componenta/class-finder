<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Config\ConfigKey as BaseConfigKey;

/**
 * Configuration keys for class discovery.
 */
final class ConfigKey extends BaseConfigKey
{
    public const string FILTERS = 'Componenta\ClassFinder:filters';
    public const string LISTENERS = 'Componenta\ClassFinder:listeners';
}
