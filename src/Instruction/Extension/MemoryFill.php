<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class MemoryFill implements InstructionInterface
{
    public function __construct(
        public readonly int $memory_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $memory_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($memory_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $memory = $store->memory[$this->memory_idx];
        $count = $memory->operand(array_pop($stack));
        $value = array_pop($stack)->value;
        $memory->fill($memory->operand(array_pop($stack)), $value, $count);
    }
}
