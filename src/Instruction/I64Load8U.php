<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Util\WasmReader;

class I64Load8U implements InstructionInterface
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
        $address = array_pop($stack);
        $bytes = $store->memory[$this->memory_idx]->load($address, $this->offset, 1);
        $stack[] = new I64(ord($bytes));
    }
}
