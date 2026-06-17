<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Arrayable\Arrayable;
use Componenta\Tokenizer\ClassInfo;
use Componenta\Filter\FilterableInterface;

/**
 * @extends \IteratorAggregate<string, ClassInfo>
 * @extends Arrayable<int, ClassInfo>
 */
interface ClassIteratorInterface extends \IteratorAggregate, \Countable, Arrayable, FilterableInterface
{
}