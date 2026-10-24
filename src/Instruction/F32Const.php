<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Util\WasmReader;

class F32Const implements InstructionInterface
{
  public function __construct(
    // The raw bits, so signalling NaNs keep their payload
    public readonly int $bits,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    return new self(WasmReader::readUint32($input, $offset));
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    array_push($stack, F32::fromBits($this->bits));
  }
}
