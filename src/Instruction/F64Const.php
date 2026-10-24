<?php

namespace Oatmael\WasmPhp\Instruction;

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Util\WasmReader;

class F64Const implements InstructionInterface
{
  public function __construct(
    public readonly float $value,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $value = WasmReader::readFloat64($input, $offset);
    return new self($value);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    array_push($stack, new F64($this->value));
  }
}
