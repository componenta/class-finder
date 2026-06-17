<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Tokenizer\TokenizerInterface;

interface ClassFinderInterface
{
    /**
     * @param string|string[] $dirs
     * @param string|string[] $exclude
     * @param int-mask-of<TokenizerInterface::SEARCH_*> $mode
     */
    public function find(
        string|array $dirs,
        string|array $exclude = [],
        int $mode = TokenizerInterface::SEARCH_ALL,
    ): ClassIteratorInterface;
}