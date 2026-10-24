<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Util\WasmReader;

class I64Load32U implements InstructionInterface
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
        $addr = array_pop($stack);
        if (!($addr instanceof I32)) {
            throw new Exception('Invalid stack params for i64.load32_u opcode');
        }

        $memory = $store->memory[0]; // Only 1 memory is valid for v1
        $value = unpack('V', $memory->read($addr->toUnsigned()->value + $this->offset, 4))[1];

        array_push($stack, new I64($value));
    }
}
