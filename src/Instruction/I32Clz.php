<?php

namespace Oatmael\WasmPhp\Instruction;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;

#[Opcode(StandardOpcode::i32_clz)]
class I32Clz implements InstructionInterface
{
  protected const DEBRUIJIN_32 = [
    0, 31, 9, 30, 3, 8, 13, 29, 2, 5, 7, 21, 12, 24, 28, 19,
    1, 10, 4, 14, 6, 22, 25, 20, 11, 15, 23, 26, 16, 27, 17, 18
  ];

  public static function fromInput(string $input, int &$offset): InstructionInterface
  {
    return new self();
  }

  public function execute(array &$stack, array &$call_stack, Store $store)
  {
    $test = array_pop($stack);
    if (!$test instanceof I32) {
      throw new Exception('Invalid stack params for i32.clz opcode');
    }

    $x = $test->getValue();
    if ($x === 0) {
      array_push($stack, new I32(32));
    } else {
      $x |= $x >> 1;
      $x |= $x >> 2;
      $x |= $x >> 4;
      $x |= $x >> 8;
      $x |= $x >> 16;
      $x++;

      array_push($stack, new I32(self::DEBRUIJIN_32[($x * 0x076be629) >> 27]));
    }
  }
}
