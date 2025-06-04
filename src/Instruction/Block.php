<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::block)]
class Block implements InstructionInterface
{
  public function __construct(
    public readonly int $block_type,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $block_type = WasmReader::readLEB128int32($input, $offset);
    return new self($block_type);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    throw new Exception('Not implemented: block opcode');
  }
}
