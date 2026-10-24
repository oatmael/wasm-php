<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

class BrTable implements InstructionInterface
{
  public function __construct(
    public readonly array $break_depths,
    public readonly int $default_depth,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $label_count = WasmReader::readLEB128Uint32($input, $offset);
    $break_depths = [];
    for ($i = 0; $i < $label_count; $i++) {
      $break_depths[] = WasmReader::readLEB128Uint32($input, $offset);
    }
    $default_idx = WasmReader::readLEB128Uint32($input, $offset);
    return new self($break_depths, $default_idx);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    $condition = array_pop($stack);
    $break_depth = $this->break_depths[$condition->toUnsigned()->value] ?? $this->default_depth;
    $store->branch($stack, $call_stack, $break_depth);
  }
}
