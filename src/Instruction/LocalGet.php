<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::local_get)]
class LocalGet implements InstructionInterface {
    public function __construct(public readonly int $local_idx) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface { 
        $local_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($local_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) { 
        array_push($stack, end($call_stack)->locals[$this->local_idx]);
    }

}