<?php

namespace Oatmael\WasmPhp\Type;

use Exception;
use Oatmael\WasmPhp\Exception\TrapException;

class Table {
    // Tables beyond this many elements are refused, as each element takes a PHP array slot
    public const MAX_ELEMENTS = 10_000_000;

    public array $elements;

    public function __construct(
        public readonly RefType $element_type,
        int $min,
        public readonly ?int $max = null,
        // Tables indexed by i64 (table64)
        public readonly bool $is64 = false,
        ?ValueInterface $init = null,
    )
    {
        if ($min > self::MAX_ELEMENTS) {
            throw new Exception("Can't allocate a table of $min elements");
        }

        $this->elements = $min > 0 ? array_fill(0, $min, $init ?? new NullRef()) : [];
    }

    public function size(): int
    {
        return count($this->elements);
    }

    public function get(int $index): ValueInterface
    {
        return $this->elements[$index] ?? throw new TrapException('out of bounds table access');
    }

    public function set(int $index, ValueInterface $value): void
    {
        $this->checkBounds($index, 1);
        $this->elements[$index] = $value;
    }

    // Returns the previous size, or -1 if the table can't grow by that much
    public function grow(int $count, ValueInterface $init): int
    {
        $previous = $this->size();
        $max = min($this->max ?? PHP_INT_MAX, $this->is64 ? PHP_INT_MAX : 0xFFFFFFFF, self::MAX_ELEMENTS);
        if ($count < 0 || $count > $max - $previous) {
            return -1;
        }

        if ($count > 0) {
            array_push($this->elements, ...array_fill(0, $count, $init));
        }

        return $previous;
    }

    // Replace $count elements from $index with $values, which must all fit
    public function write(int $index, array $values): void
    {
        $this->checkBounds($index, count($values));
        array_splice($this->elements, $index, count($values), $values);
    }

    // Read $count elements from $index, which must all be in bounds
    public function read(int $index, int $count): array
    {
        $this->checkBounds($index, $count);
        return array_slice($this->elements, $index, $count);
    }

    // Converts an i32 or i64 index or length operand to an int
    public function operand(ValueInterface $operand): int
    {
        // Values above 2^63 wrap negative, which is out of bounds for any table
        return $operand instanceof I64 ? ($operand->value < 0 ? PHP_INT_MAX : $operand->value) : $operand->value & 0xFFFFFFFF;
    }

    public function checkBounds(int $index, int $count): void
    {
        if ($index < 0 || $count < 0 || $index > $this->size() - $count) {
            throw new TrapException('out of bounds table access');
        }
    }
}
