<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\ExtensionOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(ExtensionOpcode::table_grow)]
class TableGrow implements InstructionInterface
{
    public function __construct(
        public readonly int $table_idx,
        public readonly int $value,
        public readonly int $delta,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $table_idx = WasmReader::readLEB128Uint32($input, $offset);
        $value = WasmReader::readLEB128int32($input, $offset);
        $delta = WasmReader::readLEB128Uint32($input, $offset);
        return new self($table_idx, $value, $delta);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        throw new Exception('Not implemented: table.grow opcode');
    }
}
