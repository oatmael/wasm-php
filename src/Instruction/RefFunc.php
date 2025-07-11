<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::ref_func)]
class RefFunc implements InstructionInterface {
    public function __construct(public readonly int $func_idx) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $func_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($func_idx);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: ref.func opcode');
    }
}