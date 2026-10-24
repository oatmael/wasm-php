<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\WasmReader;

class V128Load16Lane implements InstructionInterface {
    public function __construct(
        public readonly int $align,
        public readonly int $offset,
        public readonly int $memory_idx,
        public readonly int $lane,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        [$align, $memory_idx, $mem_offset] = WasmReader::readMemArg($input, $offset);
        return new self($align, $mem_offset, $memory_idx, WasmReader::readUint8($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $vector = array_pop($stack);
        $address = array_pop($stack);
        $lane = $store->memory[$this->memory_idx]->load($address, $this->offset, 2);
        $stack[] = new V128(substr_replace($vector->bytes, $lane, $this->lane * 2, 2));
    }
}
