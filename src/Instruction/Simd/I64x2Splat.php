<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Instruction\SimdOpcode;

#[Opcode(SimdOpcode::i64x2_splat)]
class I64x2Splat implements InstructionInterface
{
  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    return new self();
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: i64x2.splat opcode');
  }
}
