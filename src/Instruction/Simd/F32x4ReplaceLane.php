<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(SimdOpcode::f32x4_replace_lane)]
class F32x4ReplaceLane implements InstructionInterface
{
  public function __construct(
    public readonly int $lane,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $lane = WasmReader::readUint8($input, $offset);
    return new self($lane);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: f32x4.replace_lane opcode');
  }
}
