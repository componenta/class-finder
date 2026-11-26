<?php

declare(strict_types=1);

use Componenta\ClassFinder\Filter\AnyAttributeFilter;
use Componenta\ClassFinder\Filter\AttributeSearchFilter;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\ClassFinder\Filter\InValuesFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;
use Componenta\ClassFinder\Tests\Fixture\ClassWithAttribute;
use Componenta\ClassFinder\Tests\Fixture\SampleAttribute;
use Componenta\Tokenizer\ClassInfo;

it('rejects non-string elements in configured class and attribute name lists', function (mixed $invalid): void {
    $values = ['valid' => 'Example', 'invalid' => $invalid];

    expect(fn () => new AnyAttributeFilter($values))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new AttributeSearchFilter($values))->toThrow(InvalidArgumentException::class)
        ->and(fn () => PatternFilter::in($values))->toThrow(InvalidArgumentException::class);
})->with([
    'integer' => [42],
    'boolean' => [true],
    'null' => [null],
    'array' => [[]],
    'object' => [new stdClass()],
]);

it('does not execute string conversion on an invalid filter name', function (): void {
    $invalid = new class implements Stringable {
        public int $calls = 0;
        public function __toString(): string
        {
            ++$this->calls;
            return SampleAttribute::class;
        }
    };

    expect(fn () => new AnyAttributeFilter([$invalid]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new AttributeSearchFilter([$invalid]))->toThrow(InvalidArgumentException::class)
        ->and(fn () => new InValuesFilter([$invalid]))->toThrow(InvalidArgumentException::class)
        ->and($invalid->calls)->toBe(0);
});

it('retains duplicate and associative string selections without caller reference leaks', function (): void {
    $attribute = '\\' . strtolower(SampleAttribute::class);
    $name = 'Example';
    $any = new AnyAttributeFilter(['first' => &$attribute, 'duplicate' => SampleAttribute::class]);
    $search = AttributeSearchFilter::hasAllAttributes(['first' => &$attribute, 'duplicate' => SampleAttribute::class]);
    $names = PatternFilter::in(['first' => &$name, 'duplicate' => 'Example']);

    $attribute = 'OtherAttribute';
    $name = 'Other';

    $info = ClassInfoFactory::fromClass(ClassWithAttribute::class);
    expect($any->accept($info))->toBeTrue()
        ->and($search->accept($info))->toBeTrue()
        ->and($names->accept(new ClassInfo('Example')))->toBeTrue()
        ->and($names->accept(new ClassInfo('Other')))->toBeFalse();
});
