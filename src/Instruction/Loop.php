<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\ControlStackEntry;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;

class Loop implements InstructionInterface
{
  public function __construct(
    public readonly ValueType|int $block_type,
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
    
    $break_target = $frame->program_counter - 1;

    $block_type = $store->resolveBlockType($this->block_type);

    array_push($frame->control_stack, new ControlStackEntry(
      break_target: $break_target,
      return_type: $block_type,
    ));
  }
}
