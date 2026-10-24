<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

class F32Store implements InstructionInterface
{
    public function __construct(
        public readonly int $align,
        public readonly int $offset,
        public readonly int $memory_idx = 0,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        [$align, $memory_idx, $mem_offset] = WasmReader::readMemArg($input, $offset);
        return new self($align, $mem_offset, $memory_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $value = array_pop($stack);
        $address = array_pop($stack);
        $store->memory[$this->memory_idx]->store($address, $this->offset, pack('V', $value->getBits()));
    }
}
