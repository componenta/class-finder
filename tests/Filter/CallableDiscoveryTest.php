<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\CallableFilter;
use Componenta\Tokenizer\ClassInfo;
use Componenta\Tokenizer\DeclarationType;

it('recognizes an autoloadable invokable class before any caller loads it', function (): void {
    $name = 'ClassFinderLazyInvokableRegressionFixture';
    $loader = static function (string $class) use ($name): void {
        if ($class === $name) {
            eval('class ClassFinderLazyInvokableRegressionFixture { public function __invoke(): void {} }');
        }
    };
    spl_autoload_register($loader);

    try {
        expect(class_exists($name, false))->toBeFalse();
        $filter = new CallableFilter();
        $info = new ClassInfo($name);

        expect($filter->accept($info))->toBeTrue()
            ->and($filter->accept($info))->toBeTrue();
    } finally {
        spl_autoload_unregister($loader);
    }
});

it('does not classify non-concrete declarations as callable classes', function (): void {
    $filter = new CallableFilter();

    expect($filter->accept(new ClassInfo('UnloadedContract', DeclarationType::Interface_)))->toBeFalse()
        ->and($filter->accept(new ClassInfo('UnloadedTrait', DeclarationType::Trait_)))->toBeFalse()
        ->and($filter->accept(new ClassInfo('UnloadedAbstract', isAbstract: true)))->toBeFalse()
        ->and($filter->accept(new ClassInfo('UnloadedEnum', DeclarationType::Enum_)))->toBeFalse();
});
