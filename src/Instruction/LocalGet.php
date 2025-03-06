<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::local_get)]
class LocalGet implements InstructionInterface {
    public function __construct(public readonly int $local_idx) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface { 
        $local_idx = WasmReader::readLEB128Uint32($input, $offset);
        return new self($local_idx);
    }

    public function execute() { 
        throw new Exception('Unimplemented');
    }

}