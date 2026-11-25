<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Benchmarks;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
final class RouteTag {}

#[Attribute(Attribute::TARGET_METHOD)]
final class MethodTag {}

interface ContractA {}
interface ContractB {}

#[RouteTag]
final class FixtureController implements ContractA, ContractB
{
    #[MethodTag]
    public function __invoke(): void {}
}

