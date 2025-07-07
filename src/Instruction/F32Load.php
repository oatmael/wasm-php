<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::f32_load)]
class F32Load implements InstructionInterface {
    public function __construct(
        private readonly int $align,
        private readonly int $offset,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $mem_align = WasmReader::readLEB128Uint32($input, $offset);
        $mem_offset = WasmReader::readLEB128Uint32($input, $offset);
        return new self($mem_align, $mem_offset);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        /** @var Memory $memory */
        $memory = $store->memory[0]; // Only 1 memory is valid for v1
        $addr = array_pop($stack);

        $at = $addr->toUnsigned()->value + $this->offset;

        $values = array_slice($memory->data, $at, 4);
        $value = unpack("g", pack("C*", ...$values))[1];
        array_push($stack, new F32($value));
    }
}