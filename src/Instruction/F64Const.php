<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Util\WasmReader;

#[Opcode(StandardOpcode::f64_const)]
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
    throw new Exception('Not implemented: f64.const opcode');
  }
}
