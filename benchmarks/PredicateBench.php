<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Benchmarks;

use Componenta\ClassFinder\Filter\AttributeSearchFilter;
use Componenta\ClassFinder\Filter\CallableFilter;
use Componenta\ClassFinder\Filter\ImplementsFilter;
use Componenta\ClassFinder\Filter\InstantiableFilter;
use Componenta\ClassFinder\Filter\PatternFilter;
use Componenta\Filter\PredicateInterface;
use Componenta\Tokenizer\ClassInfo;
use PhpBench\Attributes as Bench;
use RuntimeException;

require_once __DIR__ . '/Fixture/PredicateFixtures.php';

#[Bench\Iterations(7)]
#[Bench\Revs(1000)]
final class PredicateBench
{
    private PredicateInterface $predicate;
    private ClassInfo $info;
    private bool $result;

    public function providePredicates(): iterable
    {
        foreach (['exact', 'wildcard', 'attribute', 'deep-attribute', 'interfaces', 'instantiable', 'callable'] as $kind) {
            yield $kind => ['kind' => $kind];
        }
    }

    public function prepare(array $params): void
    {
        $this->predicate = match ($params['kind']) {
            'exact' => PatternFilter::exactFqn(FixtureController::class),
            'wildcard' => PatternFilter::fqn('*\\Benchmarks\\Fixture?ontroller'),
            'attribute' => AttributeSearchFilter::hasAttribute(RouteTag::class),
            'deep-attribute' => AttributeSearchFilter::hasAllAttributes([RouteTag::class, MethodTag::class], deepSearch: true),
            'interfaces' => new ImplementsFilter([ContractA::class, ContractB::class]),
            'instantiable' => new InstantiableFilter(),
            'callable' => new CallableFilter(),
        };
        $this->info = new ClassInfo(FixtureController::class, isFinal: true);
        if (!$this->predicate->accept($this->info)) {
            throw new RuntimeException('Invalid predicate fixture.');
        }
    }

    public function verify(array $params): void
    {
        if (!$this->result) {
            throw new RuntimeException('Predicate did not match the fixture.');
        }
    }

    #[Bench\ParamProviders('providePredicates')]
    #[Bench\BeforeMethods('prepare')]
    #[Bench\AfterMethods('verify')]
    public function benchWarmPredicate(array $params): void
    {
        $this->result = $this->predicate->accept($this->info);
    }

    #[Bench\ParamProviders('providePredicates')]
    #[Bench\BeforeMethods('prepare')]
    #[Bench\AfterMethods('verify')]
    public function benchFreshMetadata(array $params): void
    {
        $this->result = $this->predicate->accept(new ClassInfo(FixtureController::class, isFinal: true));
    }
}
