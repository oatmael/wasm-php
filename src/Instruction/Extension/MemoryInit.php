<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class MemoryInit implements InstructionInterface
{
    public function __construct(
        public readonly int $data_idx,
        public readonly int $memory_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $data_idx = WasmReader::readLEB128Uint32($input, $offset);
        $memory_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($data_idx, $memory_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $memory = $store->memory[$this->memory_idx];
        $count = array_pop($stack)->value & 0xFFFFFFFF;
        $src = array_pop($stack)->value & 0xFFFFFFFF;
        $store->initMemory($memory, $this->data_idx, $memory->operand(array_pop($stack)), $src, $count);
    }
}
