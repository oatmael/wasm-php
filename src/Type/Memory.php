<?php

namespace Oatmael\WasmPhp\Type;

use Exception;
use Oatmael\WasmPhp\Exception\TrapException;

class Memory {

    public const PAGE_SIZE = 65536;

    // The memory's bytes, as a binary string
    public string $data;

    public function __construct(
        public readonly int $min,
        public readonly ?int $max = null,
        // Memories addressed by i64 (memory64)
        public readonly bool $is64 = false,
    )
    {
        if ($min > $this->maxPages() || !self::canAllocate($min)) {
            throw new Exception("Can't allocate a memory of $min pages");
        }

        $this->data = str_repeat("\0", $min * self::PAGE_SIZE);
    }

    public static function fromType(MemoryType $type): self
    {
        return new self($type->min, $type->max, $type->is64);
    }

    public function maxPages(): int
    {
        return $this->max ?? ($this->is64 ? 1 << 48 : 1 << 16);
    }

    public function pages(): int
    {
        return intdiv(strlen($this->data), self::PAGE_SIZE);
    }

    public function read(int $address, int $length): string
    {
        $this->checkBounds($address, $length);

        return substr($this->data, $address, $length);
    }

    public function write(int $address, string $bytes): void
    {
        $length = strlen($bytes);
        $this->checkBounds($address, $length);

        // Writing bytes in place avoids copying the whole memory for small writes
        if ($length <= 16) {
            for ($i = 0; $i < $length; $i++) {
                $this->data[$address + $i] = $bytes[$i];
            }
        } else {
            $this->data = substr_replace($this->data, $bytes, $address, $length);
        }
    }

    public function fill(int $address, int $value, int $length): void
    {
        $this->checkBounds($address, $length);
        $this->write($address, str_repeat(chr($value & 0xFF), $length));
    }

    // Returns the previous size in pages, or -1 if the memory can't grow by that much
    public function grow(int $pages): int
    {
        $previous = $this->pages();
        if ($pages < 0 || $pages > $this->maxPages() - $previous || !self::canAllocate($pages)) {
            return -1;
        }

        $this->data .= str_repeat("\0", $pages * self::PAGE_SIZE);
        return $previous;
    }

    // The address an instruction accesses: its i32 or i64 operand plus the instruction's static offset
    public function address(ValueInterface $operand, int $offset, int $size): int
    {
        $base = $operand instanceof I64 ? $operand->value : $operand->value & 0xFFFFFFFF;

        // Unsigned i64 operands and offsets above 2^63 wrap negative, but are out of bounds anyway
        if ($base < 0 || $offset < 0 || $base > PHP_INT_MAX - $offset) {
            throw new TrapException('out of bounds memory access');
        }

        $this->checkBounds($base + $offset, $size);
        return $base + $offset;
    }

    public function load(ValueInterface $operand, int $offset, int $size): string
    {
        return substr($this->data, $this->address($operand, $offset, $size), $size);
    }

    public function store(ValueInterface $operand, int $offset, string $bytes): void
    {
        $this->write($this->address($operand, $offset, strlen($bytes)), $bytes);
    }

    // Converts an i32 or i64 operand of a bulk memory instruction to an address or length
    public function operand(ValueInterface $operand): int
    {
        // Values above 2^63 wrap negative, which is out of bounds for any memory
        return $operand instanceof I64 ? ($operand->value < 0 ? PHP_INT_MAX : $operand->value) : $operand->value & 0xFFFFFFFF;
    }

    protected function checkBounds(int $address, int $length): void
    {
        if ($address < 0 || $length < 0 || $address > strlen($this->data) - $length) {
            throw new TrapException("out of bounds memory access: $length bytes at address $address");
        }
    }

    // Wasm allows growth to fail, so refuse growth that would exhaust PHP's memory limit rather than crashing
    protected static function canAllocate(int $pages): bool
    {
        if ($pages > intdiv(PHP_INT_MAX, self::PAGE_SIZE)) {
            return false;
        }

        $limit = ini_parse_quantity(ini_get('memory_limit'));
        return $limit <= 0 || $pages * self::PAGE_SIZE <= ($limit - memory_get_usage()) / 2;
    }
}
