<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::call)]
class Call implements InstructionInterface {
    public function __construct(public readonly int $function_idx) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface { 
        $function_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($function_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) { 
        $store->pushFrame($stack, $call_stack, $this->function_idx);
    }
}