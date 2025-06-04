<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::call_indirect)]
class CallIndirect implements InstructionInterface
{
  public function __construct(
    public readonly int $type_idx,
    public readonly int $table_idx,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $type_idx = WasmReader::readLEB128Uint32($input, $offset);
    $table_idx = WasmReader::readLEB128Uint32($input, $offset);
    return new self($type_idx, $table_idx);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: call_indirect opcode');
  }
}
