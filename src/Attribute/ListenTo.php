<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Attribute;

use Attribute;

/**
 * Declares which attribute a listener is interested in.
 * Used by the caching layer to pre-filter classes per listener.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class ListenTo
{
    /**
     * @param class-string $attribute Attribute class to filter by.
     * @param bool $deepSearch When true, also search methods, properties, and constants.
     */
    public function __construct(
        public string $attribute,
        public bool $deepSearch = false,
    ) {}
}
