<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Util\WasmReader;

// select with explicit operand types, which are only needed for validation
class SelectT extends Select {
    public function __construct(public readonly array $types) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        $count = WasmReader::readLEB128Uint32($input, $offset);
        $types = [];
        for ($i = 0; $i < $count; $i++) {
            $types[] = WasmReader::readValueType($input, $offset);
        }

        return new self($types);
    }
}
