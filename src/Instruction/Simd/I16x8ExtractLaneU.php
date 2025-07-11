<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(SimdOpcode::i16x8_extract_lane_u)]
class I16x8ExtractLaneU implements InstructionInterface
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
    throw new Exception('Not implemented: i16x8.extract_lane_u opcode');
  }
}
