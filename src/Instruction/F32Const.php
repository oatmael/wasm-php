<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::f32_const)]
class F32Const implements InstructionInterface
{
  public function __construct(
    public readonly float $value,
  ) {}

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    $value = WasmReader::readFloat32($input, $offset);
    return new self($value);
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    array_push($stack, new F32($this->value));
  }
}
