<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Filter;

use Componenta\ClassFinder\Filter\InstantiableFilter;
use Componenta\ClassFinder\Tests\Fixture\ClassInfoFactory;

/** @param class-string $class */
it('checks constructor visibility when selecting instantiable classes', function (string $class, bool $expected): void {
    expect((new InstantiableFilter())->accept(ClassInfoFactory::fromClass($class)))->toBe($expected);
})->with([
    'private constructor' => [PrivateConstructorFixture::class, false],
    'protected constructor' => [ProtectedConstructorFixture::class, false],
    'inherited protected constructor' => [InheritedConstructorFixture::class, false],
    'required public argument' => [RequiredConstructorFixture::class, true],
    'public override' => [PublicConstructorFixture::class, true],
]);

it('rejects enum declarations and objects that only expose an isConcrete property', function (): void {
    $filter = new InstantiableFilter();

    expect($filter->accept(ClassInfoFactory::fromEnum(InstantiableEnumFixture::class)))->toBeFalse()
        ->and($filter->accept((object) ['isConcrete' => true]))->toBeFalse();
});

final class PrivateConstructorFixture
{
    private function __construct()
    {
    }
}

class ProtectedConstructorFixture
{
    protected function __construct()
    {
    }
}

final class InheritedConstructorFixture extends ProtectedConstructorFixture
{
}

final class RequiredConstructorFixture
{
    public function __construct(public string $value)
    {
    }
}

final class PublicConstructorFixture extends ProtectedConstructorFixture
{
    public function __construct()
    {
    }
}

enum InstantiableEnumFixture
{
    case Value;
}
