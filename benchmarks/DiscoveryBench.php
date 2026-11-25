<?php

declare(strict_types=1);

namespace Componenta\ClassFinder\Benchmarks;

use Componenta\ClassFinder\ClassFinder;
use Componenta\ClassFinder\ClassIterator;
use Componenta\ClassFinder\Filter\PatternFilter;
use PhpBench\Attributes as Bench;
use RuntimeException;

#[Bench\Iterations(5)]
#[Bench\Revs(1)]
final class DiscoveryBench
{
    private string $directory;
    private ClassIterator $warm;
    private array $map;
    private int $result;

    public function provideFileCounts(): iterable
    {
        foreach ([100, 1000, 10000] as $size) {
            yield (string) $size => ['size' => $size];
        }
    }

    public function provideMapSizes(): iterable
    {
        foreach ([1000, 10000, 100000] as $size) {
            yield (string) $size => ['size' => $size];
        }
    }

    public function prepareFiles(array $params): void
    {
        $this->directory = sys_get_temp_dir() . '/class-finder-bench-' . bin2hex(random_bytes(12));
        if (!mkdir($this->directory, 0700)) {
            throw new RuntimeException('Cannot create benchmark directory.');
        }

        for ($i = 0; $i < $params['size']; $i++) {
            if (file_put_contents($this->directory . "/Entry{$i}.php", "<?php namespace BenchFixture; class Entry{$i} {}") === false) {
                throw new RuntimeException('Cannot write benchmark fixture.');
            }
        }
    }

    public function verifyFiles(array $params): void
    {
        foreach (glob($this->directory . '/*.php') ?: [] as $path) {
            unlink($path);
        }
        rmdir($this->directory);
        $this->verifyCount($params);
    }

    public function prepareMap(array $params): void
    {
        $this->map = [];
        for ($i = 0; $i < $params['size']; $i++) {
            $this->map[] = [
                'file' => "/fixture/Entry{$i}.php",
                'name' => "BenchFixture\\Entry{$i}",
                'type' => 'class',
                'abstract' => false,
                'final' => false,
                'readonly' => false,
            ];
        }
    }

    public function prepareReplay(array $params): void
    {
        $this->prepareMap($params);
        $this->warm = ClassIterator::fromMap($this->map)->withFilter(new PatternFilter('Entry*'));
        if (count($this->warm) !== $params['size']) {
            throw new RuntimeException('Invalid replay benchmark fixture.');
        }
    }

    public function verifyCount(array $params): void
    {
        if ($this->result !== $params['size']) {
            throw new RuntimeException('Unexpected discovery count.');
        }
    }

    #[Bench\ParamProviders('provideFileCounts')]
    #[Bench\BeforeMethods('prepareFiles')]
    #[Bench\AfterMethods('verifyFiles')]
    public function benchColdScan(array $params): void
    {
        $this->result = count(new ClassFinder()->find($this->directory));
    }

    #[Bench\ParamProviders('provideMapSizes')]
    #[Bench\BeforeMethods('prepareMap')]
    #[Bench\AfterMethods('verifyCount')]
    public function benchRestoreAndTraverse(array $params): void
    {
        $this->result = count(ClassIterator::fromMap($this->map));
    }

    #[Bench\ParamProviders('provideMapSizes')]
    #[Bench\BeforeMethods('prepareReplay')]
    #[Bench\AfterMethods('verifyCount')]
    public function benchWarmFilteredCount(array $params): void
    {
        $this->result = count($this->warm);
    }

    #[Bench\ParamProviders('provideMapSizes')]
    #[Bench\BeforeMethods('prepareReplay')]
    #[Bench\AfterMethods('verifyCount')]
    public function benchWarmFilteredArray(array $params): void
    {
        $this->result = count($this->warm->toArray());
    }
}
