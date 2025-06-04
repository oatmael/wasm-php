<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Instruction\Opcode;
use Oatmael\WasmPhp\Instruction\SimdOpcode;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(SimdOpcode::i8x16_shuffle)]
class I8x16Shuffle implements InstructionInterface
{
  public function __construct(
    public readonly string $lanes,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $lanes = WasmReader::readBytes($input, $offset, 16);
    return new self($lanes);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: i8x16.shuffle opcode');
  }
}
