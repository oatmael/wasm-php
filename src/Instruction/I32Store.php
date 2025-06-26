<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::i32_store)]
class I32Store implements InstructionInterface {
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

        $at = $addr->toUnsigned()->value + $this->offset;

        $memory = $store->memory[0]; // Only 1 memory is valid for v1
        $values = array_values(unpack("C4", pack("V", $value->getValue())));
        array_splice($memory->data, $at, 4, $values);
    }

}