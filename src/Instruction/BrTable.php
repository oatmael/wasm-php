<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::br_table)]
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
    /** @var Frame $frame */
    $frame = end($call_stack);

    $condition = array_pop($stack);
    $break_depth = $this->break_depths[$condition->value] ?? $this->default_depth;
    if ($break_depth >= count($frame->control_stack)) {
      throw new Exception('Invalid br_table depth: ' . $break_depth);
    }

    $control_stack = [];
    for ($i = 0; $i <= $break_depth; $i++) {
      $control_stack[] = array_pop($frame->control_stack);
    }

    /** @var ControlStackEntry $result */
    $result = end($control_stack);
    $frame->program_counter = $result->break_target;
  }
}
