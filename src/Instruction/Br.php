<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

class Br implements InstructionInterface
{
  public function __construct(
    public readonly int $depth,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $depth = WasmReader::readLEB128Uint32($input, $offset);
    return new self($depth);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    $store->branch($stack, $call_stack, $this->depth);
  }
}
