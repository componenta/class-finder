<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Benchmarks;

use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\ClassListenerNotifier;
use Componenta\ClassFinder\ClassListenerProvider;
use Componenta\ClassFinder\FinalizableListenerInterface;
use Componenta\Tokenizer\ClassInfo;
use PhpBench\Attributes as Bench;
use RuntimeException;

final class CountingListener implements FinalizableListenerInterface
{
    public int $handled = 0;
    public int $finalized = 0;

    public function handle(ClassInfo $info): void
    {
        ++$this->handled;
    }

    public function finalize(): void
    {
        ++$this->finalized;
    }
}

#[Bench\Iterations(7)]
#[Bench\Revs(1)]
final class ListenerBench
{
    private ClassIterator $classes;
    private ClassListenerNotifier $notifier;
    /** @var list<CountingListener> */
    private array $listeners;

    public function provideLoads(): iterable
    {
        foreach ([1, 10, 100] as $listeners) {
            yield (string) $listeners => ['listeners' => $listeners, 'classes' => 1000];
        }
    }

    public function prepare(array $params): void
    {
        $entries = [];
        for ($i = 0; $i < $params['classes']; $i++) {
            $entries["/fixture/{$i}.php"] = new ClassInfo("BenchFixture\\Entry{$i}");
        }
        $this->classes = new ClassIterator($entries);
        $this->listeners = [];
        for ($i = 0; $i < $params['listeners']; $i++) {
            $this->listeners[] = new CountingListener();
        }
        $this->notifier = new ClassListenerNotifier(new ClassListenerProvider($this->listeners));
    }

    public function verify(array $params): void
    {
        foreach ($this->listeners as $listener) {
            if ($listener->handled !== $params['classes'] || $listener->finalized !== 1) {
                throw new RuntimeException('Unexpected listener result.');
            }
        }
    }

    #[Bench\ParamProviders('provideLoads')]
    #[Bench\BeforeMethods('prepare')]
    #[Bench\AfterMethods('verify')]
    public function benchNotify(array $params): void
    {
        $this->notifier->notify($this->classes);
    }
}
