<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::br_table)]
class BrTable implements InstructionInterface
{
  public function __construct(
    public readonly array $label_indices,
    public readonly int $default_idx,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $label_count = WasmReader::readLEB128Uint32($input, $offset);
    $label_indices = [];
    for ($i = 0; $i < $label_count; $i++) {
      $label_indices[] = WasmReader::readLEB128Uint32($input, $offset);
    }
    $default_idx = WasmReader::readLEB128Uint32($input, $offset);
    return new self($label_indices, $default_idx);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: br_table opcode');
  }
}
