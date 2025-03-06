<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;

#[Opcode(StandardOpcode::i32_add)]
class I32Add implements InstructionInterface {
    public static function fromInput(string $input, int &$offset): InstructionInterface { 
        return new self();
    }

    public function execute() { 
        throw new Exception('Unimplemented');
    }

}