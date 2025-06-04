<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::br_if)]
class BrIf implements InstructionInterface
{
  public function __construct(
    public readonly int $label_idx,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $label_idx = WasmReader::readLEB128Uint32($input, $offset);
    return new self($label_idx);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: br_if opcode');
  }
}
