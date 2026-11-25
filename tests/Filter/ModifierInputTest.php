<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\IsAbstractFilter;
use Componenta\ClassFinder\Filter\IsFinalFilter;

it('rejects unrelated objects regardless of their modifier properties', function (mixed $input): void {
    expect(new IsAbstractFilter()->accept($input))->toBeFalse()
        ->and(new IsFinalFilter()->accept($input))->toBeFalse();
})->with([
    'matching booleans' => [(object) ['isAbstract' => true, 'isFinal' => true]],
    'integer properties' => [(object) ['isAbstract' => 1, 'isFinal' => 1]],
    'null' => [null],
    'array' => [['isAbstract' => true, 'isFinal' => true]],
]);
