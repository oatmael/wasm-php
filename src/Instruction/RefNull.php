<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::ref_null)]
class RefNull implements InstructionInterface {
    public function __construct(public readonly int $ref_type) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $ref_type = WasmReader::readLEB128Uint32($input, $offset);
        return new self($ref_type);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: ref.null opcode');
    }
}