<?php

declare(strict_types=1);

use Componenta\ClassFinder\Factory\ClassFinderFactory;
use Componenta\Tokenizer\ClassInfo;
use Componenta\Tokenizer\TokenizerInterface;
use Psr\Container\ContainerInterface;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

it('rejects an explicitly registered service with an incompatible implementation', function (string $id, mixed $service): void {
    $container = new class($id, $service) implements ContainerInterface {
        public function __construct(private string $id, private mixed $service) {}
        public function has(string $id): bool { return $id === $this->id; }
        public function get(string $id): mixed
        {
            if (!$this->has($id)) {
                throw new RuntimeException('Unexpected service lookup.');
            }

            return $this->service;
        }
    };

    expect(fn () => (new ClassFinderFactory())($container))
        ->toThrow(InvalidArgumentException::class, $id);
})->with([
    'null tokenizer' => [TokenizerInterface::class, null],
    'wrong tokenizer' => [TokenizerInterface::class, new stdClass()],
    'null logger' => [LoggerInterface::class, null],
    'wrong logger' => [LoggerInterface::class, new stdClass()],
]);

it('uses explicitly supplied tokenizer and logger services without resolving absent defaults', function (): void {
    $failure = new RuntimeException('Custom tokenizer failure.');
    $tokenizer = new class($failure) implements TokenizerInterface {
        public function __construct(private Throwable $failure) {}
        public function parse(string $source, int $mode = self::SEARCH_ALL): array
        {
            throw $this->failure;
        }
    };
    $logger = new class extends AbstractLogger {
        public array $records = [];
        public function log($level, string|Stringable $message, array $context = []): void
        {
            $this->records[] = [$level, $message, $context];
        }
    };
    $container = new class($tokenizer, $logger) implements ContainerInterface {
        public function __construct(private TokenizerInterface $tokenizer, private LoggerInterface $logger) {}
        public function has(string $id): bool
        {
            return $id === TokenizerInterface::class || $id === LoggerInterface::class;
        }
        public function get(string $id): mixed
        {
            return match ($id) {
                TokenizerInterface::class => $this->tokenizer,
                LoggerInterface::class => $this->logger,
                default => throw new RuntimeException('Unexpected service lookup.'),
            };
        }
    };
    $classes = (new ClassFinderFactory())($container)->find(dirname(__DIR__) . '/Fixture/ScanTarget');

    expect(fn () => count($classes))->toThrow($failure)
        ->and($logger->records)->toHaveCount(1)
        ->and($logger->records[0][2]['exception'])->toBe($failure);
});
