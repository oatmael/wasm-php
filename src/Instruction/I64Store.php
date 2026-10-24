<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Util\WasmReader;

class I64Store implements InstructionInterface
{
    public function __construct(
        public readonly int $align,
        public readonly int $offset,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $mem_align = WasmReader::readLEB128Uint32($input, $offset);
        $mem_offset = WasmReader::readLEB128Uint32($input, $offset);
        return new self($mem_align, $mem_offset);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        $value = array_pop($stack);
        $addr = array_pop($stack);
        if (!($value instanceof I64) || !($addr instanceof I32)) {
            throw new Exception('Invalid stack params for i64.store opcode');
        }

        $memory = $store->memory[0]; // Only 1 memory is valid for v1
        $memory->write($addr->toUnsigned()->value + $this->offset, pack('P', $value->getValue()));
    }
}
