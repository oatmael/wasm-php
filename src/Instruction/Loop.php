<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\ControlStackEntry;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\Func;
use Oatmael\WasmPhp\Util\ValueType;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::loop)]
class Loop implements InstructionInterface
{
  public function __construct(
    public readonly int $block_type,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $block_type = WasmReader::readUint8($input, $offset);
    return new self($block_type);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    /** @var Frame $frame */
    $frame = end($call_stack);
    
    $break_target = $frame->program_counter - 1;

    $block_type = ValueType::tryFrom($this->block_type);
    if (!$block_type) {
      if ($block_type >= count($store->imports)) {
        $block_type = $store->types[$store->functions[$block_type - count($store->imports)]];
      } else {
        $import = $store->imports[$block_type];
        $block_type = $store->types[$import->idx];
      }
    } else {
      $block_type = new Func(
        params: [],
        results: [$block_type],
      );
    }

    if (!$block_type instanceof Func) {
      throw new Exception('Invalid block type');
    }

    array_push($frame->control_stack, new ControlStackEntry(
      break_target: $break_target,
      return_type: $block_type,
    ));
  }
}
