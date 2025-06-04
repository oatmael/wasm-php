<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::f32_load)]
class F32Load implements InstructionInterface {
            public function __construct(
        private readonly int $align,
        private readonly int $offset,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $align = WasmReader::readUint32($input, $offset);
        $offset = WasmReader::readUint32($input, $offset);
        return new self($align, $offset);
    }



    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: f32.load opcode');
    }
}