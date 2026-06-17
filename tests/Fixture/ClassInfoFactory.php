<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Tests\Fixture;

use Componenta\Tokenizer\ClassInfo;
use Componenta\Tokenizer\DeclarationType;

final class ClassInfoFactory
{
    public static function fromClass(string $className): ClassInfo
    {
        $reflection = new \ReflectionClass($className);

        return new ClassInfo(
            fullyQualifiedName: $className,
            type: DeclarationType::Class_,
            isAbstract: $reflection->isAbstract(),
            isFinal: $reflection->isFinal(),
            isReadonly: $reflection->isReadonly(),
        );
    }

    public static function fromInterface(string $interfaceName): ClassInfo
    {
        return new ClassInfo(
            fullyQualifiedName: $interfaceName,
            type: DeclarationType::Interface_,
        );
    }

    public static function fromTrait(string $traitName): ClassInfo
    {
        return new ClassInfo(
            fullyQualifiedName: $traitName,
            type: DeclarationType::Trait_,
        );
    }

    public static function fromEnum(string $enumName): ClassInfo
    {
        return new ClassInfo(
            fullyQualifiedName: $enumName,
            type: DeclarationType::Enum_,
        );
    }
}
