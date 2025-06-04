<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(SimdOpcode::i32x4_extract_lane)]
class I32x4ExtractLane implements InstructionInterface
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
    throw new Exception('Not implemented: i32x4.extract_lane opcode');
  }
}
