<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\ControlStackEntry;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;
use Oatmael\WasmPhp\Execution\Frame;

class Block implements InstructionInterface
{
  // Index of the matching end instruction, linked by WasmReader once the function body is decoded
  public int $end;

  public function __construct(
    public readonly ValueType|int $block_type,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface {
    $block_type = WasmReader::readBlockType($input, $offset);
    return new self($block_type);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    /** @var Frame $frame */
    $frame = end($call_stack);
    $block_type = $store->resolveBlockType($this->block_type);

    array_push($frame->control_stack, new ControlStackEntry(
      break_target: $this->end,
      stack_height: count($stack) - count($block_type->params),
      arity: count($block_type->results),
    ));
  }
}
