<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::br_if)]
class BrIf implements InstructionInterface
{
  public function __construct(
    public readonly int $depth,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $depth = WasmReader::readLEB128Uint32($input, $offset) + 1;
    return new self($depth);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    /** @var Frame $frame */
    $frame = end($call_stack);
    if ($this->depth > count($frame->control_stack)) {
      throw new Exception('Invalid br depth: ' . $this->depth);
    }

    $condition = array_pop($stack);
    if (!$condition->value) {
      return;
    }

    $control_stack = [];
    for ($i = 0; $i < $this->depth; $i++) {
      $control_stack[] = array_pop($frame->control_stack);
    }

    /** @var ControlStackEntry $result */
    $result = end($control_stack);
    $frame->program_counter = $result->break_target;
  }
}
