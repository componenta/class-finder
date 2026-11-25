<?php

declare(strict_types=1);

use Componenta\ClassFinder\ClassFinder;
use Componenta\Tokenizer\TokenizerInterface;
use Psr\Log\AbstractLogger;

it('preserves the original discovery failure even if reporting it fails', function (): void {
    $failure = new RuntimeException('Tokenizer unavailable.');
    $tokenizer = new class($failure) implements TokenizerInterface {
        public function __construct(private readonly Throwable $failure) {}
        public function parse(string $source, int $mode = self::SEARCH_ALL): array
        {
            throw $this->failure;
        }
    };
    $logger = new class extends AbstractLogger {
        public function log($level, string|Stringable $message, array $context = []): void
        {
            throw new RuntimeException('Logger unavailable.');
        }
    };
    $classes = new ClassFinder(tokenizer: $tokenizer, logger: $logger)->find(__DIR__ . '/Fixture/ScanTarget');

    foreach ([static fn () => $classes->toArray(), static fn () => count($classes)] as $operation) {
        $caught = null;
        try {
            $operation();
        } catch (Throwable $error) {
            $caught = $error;
        }

        expect($caught)->toBe($failure);
    }
});
