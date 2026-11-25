<?php

declare(strict_types=1);

namespace Componenta\ClassFinder;

use Componenta\Filter\Filterable;
use Componenta\Filter\PredicateInterface;
use Componenta\Stdlib\ReplayableIterator;
use Componenta\Tokenizer\ClassInfo;
use Componenta\Tokenizer\DeclarationType;
use RuntimeException;
use UnexpectedValueException;

/**
 * @method ClassIterator withFilter(PredicateInterface $filter, bool $prepend = false)
 * @method ClassIterator withoutFilter(PredicateInterface $filter)
 */
final class ClassIterator implements ClassIteratorInterface
{
    use Filterable {
        accept as private;
    }

    /** @var ReplayableIterator<string|int|null, ClassInfo> */
    private ReplayableIterator $iterator;

    /**
     * @param iterable<string|int|null, ClassInfo> $classes
     * @param PredicateInterface|iterable<PredicateInterface> $filters
     */
    public function __construct(
        iterable $classes,
        PredicateInterface|iterable $filters = [],
    ) {
        $this->initFilters($filters);
        if (is_array($classes)) {
            foreach ($classes as $key => $classInfo) {
                self::assertEntry($key, $classInfo);
            }
        }

        $this->iterator = new ReplayableIterator($classes);
    }

    /**
     * @param array<array-key, mixed> $map Records with file, name, type and abstract/final/readonly flags.
     * @throws RuntimeException When any record is invalid; validation completes before iteration.
     */
    public static function fromMap(array $map): self
    {
        if (!array_is_list($map)) {
            throw new RuntimeException('Discovery map must be a list of declarations.');
        }

        $validated = [];
        foreach ($map as $index => $entry) {
            if (!is_array($entry)
                || !is_string($entry['file'] ?? null) || $entry['file'] === ''
                || !is_string($entry['name'] ?? null) || $entry['name'] === ''
                || !is_string($entry['type'] ?? null) || ($type = DeclarationType::tryFrom($entry['type'])) === null
                || !is_bool($entry['abstract'] ?? null)
                || !is_bool($entry['final'] ?? null)
                || !is_bool($entry['readonly'] ?? null)
            ) {
                throw new RuntimeException(sprintf('Invalid discovery map declaration at index %d.', $index));
            }

            // A packed snapshot detaches input references and uses less memory
            // than a second associative map. Retain the already validated type.
            $validated[] = [
                $entry['file'],
                $entry['name'],
                $type,
                $entry['abstract'],
                $entry['final'],
                $entry['readonly'],
            ];
        }

        return new self((static function () use ($validated): \Generator {
            foreach ($validated as [$file, $name, $type, $abstract, $final, $readonly]) {
                yield $file => new ClassInfo(
                    fullyQualifiedName: $name,
                    type: $type,
                    isAbstract: $abstract,
                    isFinal: $final,
                    isReadonly: $readonly,
                );
            }
        })());
    }

    /** @return \Generator<string|int|null, ClassInfo> */
    public function getIterator(): \Generator
    {
        // A complete cache has already been validated by the constructor
        // (array input) or by an earlier successful traversal.
        $validate = !$this->iterator->traversed;
        $unfiltered = $this->filters === [];

        if ($unfiltered && !$validate) {
            yield from $this->iterator->cursor();
            return;
        }

        foreach ($this->iterator->cursor() as $filename => $classInfo) {
            if ($validate) {
                self::assertEntry($filename, $classInfo);
            }

            if ($unfiltered || $this->accept($classInfo, $filename)) {
                yield $filename => $classInfo;
            }
        }
    }

    private static function assertEntry(mixed $key, mixed $value): void
    {
        if (!is_string($key) && !is_int($key) && $key !== null) {
            throw new UnexpectedValueException(sprintf(
                'Discovery keys must be string, int, or null; %s given.',
                get_debug_type($key),
            ));
        }

        if (!$value instanceof ClassInfo) {
            throw new UnexpectedValueException(sprintf(
                'Discovery entries must be %s instances; %s given.',
                ClassInfo::class,
                get_debug_type($value),
            ));
        }
    }

    /** @return list<ClassInfo> */
    public function toArray(): array
    {
        if ($this->filters === [] && $this->iterator->traversed) {
            return $this->iterator->toArray();
        }

        $array = [];

        foreach ($this as $classInfo) {
            $array[] = $classInfo;
        }

        return $array;
    }

    /** @return int<0, max> */
    public function count(): int
    {
        if ($this->filters === [] && $this->iterator->traversed) {
            return $this->iterator->count();
        }

        $count = 0;

        foreach ($this as $_) {
            $count++;
        }

        return $count;
    }
}
