<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::i32_const)]
class I32Const implements InstructionInterface {
    public function __construct(public readonly I32 $const) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface { 
        $const = WasmReader::readLEB128int32($input, $offset);
        return new self(new I32($const));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) { 
        array_push($stack, $this->const);
    }

}