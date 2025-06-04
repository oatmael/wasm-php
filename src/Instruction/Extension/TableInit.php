<?php

namespace Oatmael\WasmPhp\Instruction\Extension;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\ExtensionOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(ExtensionOpcode::table_init)]
class TableInit implements InstructionInterface
{
    public function __construct(
        public readonly int $elem_idx,
        public readonly int $table_idx,
        public readonly int $offset,
    ) {}

    public static function fromInput(string $input, int &$offset): InstructionInterface
    {
        $elem_idx = WasmReader::readLEB128Uint32($input, $offset);
        $table_idx = WasmReader::readLEB128Uint32($input, $offset);
        $offset = WasmReader::readLEB128Uint32($input, $offset);
        return new self($elem_idx, $table_idx, $offset);
    }

    public function execute(array &$stack, array &$call_stack, Store $store)
    {
        throw new Exception('Not implemented: table.init opcode');
    }
}
