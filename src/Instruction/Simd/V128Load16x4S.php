<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;
use Oatmael\WasmPhp\Util\WasmReader;

// Loads 4 16-bit integers, sign extending each to 32 bits
class V128Load16x4S implements InstructionInterface {
    public function __construct(
        public readonly int $align,
        public readonly int $offset,
        public readonly int $memory_idx = 0,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        [$align, $memory_idx, $mem_offset] = WasmReader::readMemArg($input, $offset);
        return new self($align, $mem_offset, $memory_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $address = array_pop($stack);
        $lanes = array_values(unpack('v4', $store->memory[$this->memory_idx]->load($address, $this->offset, 8)));
        $stack[] = V128::fromLanes(Shape::I32x4, array_map(static fn (int $lane) => ($lane << 48) >> 48, $lanes));
    }
}
