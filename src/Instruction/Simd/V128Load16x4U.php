<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class V128Load16x4U implements InstructionInterface
{
  public function __construct(
    public readonly int $align,
    public readonly int $offset,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $align = WasmReader::readLEB128Uint32($input, $offset);
    $mem_offset = WasmReader::readLEB128Uint32($input, $offset);
    return new self($align, $mem_offset);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: v128.load16x4_u opcode');
  }
}
