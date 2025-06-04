<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(SimdOpcode::v128_load8_lane)]
class V128Load8Lane implements InstructionInterface
{
  public function __construct(
    public readonly int $align,
    public readonly int $offset,
    public readonly int $lane,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $align = WasmReader::readUint32($input, $offset);
    $offset = WasmReader::readUint32($input, $offset);
    $lane = WasmReader::readUint8($input, $offset);
    return new self($align, $offset, $lane);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: v128.load8_lane opcode');
  }
}
