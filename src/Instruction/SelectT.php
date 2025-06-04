<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::select_t)]
class SelectT implements InstructionInterface {
    public function __construct(public readonly array $types) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $count = WasmReader::readLEB128Uint32($input, $offset);
        $types = [];
        for ($i = 0; $i < $count; $i++) {
            $types[] = WasmReader::readUint8($input, $offset);
        }
        return new self($types);
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        throw new Exception('Not implemented: select_t opcode');
    }
}