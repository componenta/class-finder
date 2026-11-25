<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Tokenizer\TokenizerInterface;

interface ClassFinderInterface
{
    /**
     * Discovery is lazy. A file read or tokenizer failure aborts traversal;
     * an incomplete result must not be treated as a successful scan.
     * Each canonical file path is scanned once, even when search roots overlap.
     * Root and exclusion strings are captured at find() time; filesystem access
     * and predicate evaluation remain lazy.
     *
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