<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\ControlStackEntry;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;

class Loop implements InstructionInterface
{
  public function __construct(
    public readonly ValueType|RefType|int $block_type,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $block_type = WasmReader::readBlockType($input, $offset);
    return new self($block_type);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    /** @var Frame $frame */
    $frame = end($call_stack);
    $block_type = $store->resolveBlockType($this->block_type);

    // Branching to a loop re-executes the loop instruction, which pushes a fresh label
    array_push($frame->control_stack, new ControlStackEntry(
      break_target: $frame->program_counter - 1,
      stack_height: count($stack) - count($block_type->params),
      arity: count($block_type->params),
    ));
  }
}
