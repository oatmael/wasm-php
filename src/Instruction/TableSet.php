<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::table_set)]
class TableSet implements InstructionInterface
{
  public function __construct(
    public readonly int $table_idx,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $table_idx = WasmReader::readLEB128Uint32($input, $offset);
    return new self($table_idx);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: table.set opcode');
  }
}
