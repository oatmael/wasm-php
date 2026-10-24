<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

class TableSet implements InstructionInterface {
    public function __construct(
        public readonly int $table_idx,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface {
        return new self(WasmReader::readLEB128Uint32($input, $offset));
    }

    public function execute(array &$stack, array &$call_stack, Store $store) {
        $table = $store->tables[$this->table_idx];
        $value = array_pop($stack);
        $table->set($table->operand(array_pop($stack)), $value);
    }
}
