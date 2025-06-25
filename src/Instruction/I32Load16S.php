<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\Memory;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::i32_load16_s)]
class I32Load16S implements InstructionInterface
{
  public function __construct(
    public readonly int $align,
    public readonly int $offset,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $mem_align = WasmReader::readLEB128Uint32($input, $offset);
    $mem_offset = WasmReader::readLEB128Uint32($input, $offset);
    return new self($mem_align, $mem_offset);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    /** @var Memory $memory */
    $memory = $store->memory[0]; // Only 1 memory is valid for v1
    $addr = array_pop($stack);

    $at = $addr->value + $this->offset;

    $values = array_slice($memory->data, $at, 2);
    $value = unpack("s", pack("C*", ...$values))[1];

    $value = $value & 0xFFFF;
    if ($value & 0x8000) {
      $value = $value | ((-1 << 16) & 0xFFFFFFFF);
    }

    array_push($stack, new I32($value));
  }
}
