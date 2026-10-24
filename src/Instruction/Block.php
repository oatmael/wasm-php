<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\ControlStackEntry;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;
use Oatmael\WasmPhp\Execution\Frame;

class Block implements InstructionInterface
{
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
    
    $break_target = null;
    $block_count = 1;
    for ($i = $frame->program_counter + 1; $i < count($frame->instructions); $i++) {
      $instruction = $frame->instructions[$i];
      if ($instruction instanceof Block || $instruction instanceof Loop || $instruction instanceof IIf) {
        $block_count++;
      } elseif ($instruction instanceof End) {
        $block_count--;
        if ($block_count === 0) {
          $break_target = $i;
          break;
        }
      }
    }

    if ($break_target === null) {
      throw new Exception('No matching end found for block');
    }
    
    $block_type = $store->resolveBlockType($this->block_type);

    array_push($frame->control_stack, new ControlStackEntry(
      break_target: $break_target,
      return_type: $block_type,
    ));
  }
}
