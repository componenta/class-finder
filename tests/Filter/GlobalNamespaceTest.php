<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\Tokenizer\ClassInfo;

it('matches global declarations through an explicit empty namespace', function (): void {
    $filter = PatternFilter::exactNamespace('');

    expect($filter->accept(new ClassInfo('GlobalClass')))->toBeTrue()
        ->and($filter->accept(new ClassInfo('Named\\LocalClass')))->toBeFalse();
});
