<?php

namespace Oatmael\WasmPhp\Instruction\Simd;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Instruction\InstructionInterface;
use Oatmael\WasmPhp\Util\WasmReader;

class V128Store16Lane implements InstructionInterface
{
  public function __construct(
    public readonly int $align,
    public readonly int $offset,
    public readonly int $lane,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $align = WasmReader::readLEB128Uint32($input, $offset);
    $mem_offset = WasmReader::readLEB128Uint32($input, $offset);
    $lane = WasmReader::readUint8($input, $offset);
    return new self($align, $mem_offset, $lane);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: v128.store16_lane opcode');
  }
}
