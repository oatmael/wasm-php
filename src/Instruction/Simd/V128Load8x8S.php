<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(SimdOpcode::v128_load8x8_s)]
class V128Load8x8S implements InstructionInterface
{
  public function __construct(
    public readonly int $align,
    public readonly int $offset,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $align = WasmReader::readUint32($input, $offset);
    $offset = WasmReader::readUint32($input, $offset);
    return new self($align, $offset);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: v128.load8x8_s opcode');
  }
}
