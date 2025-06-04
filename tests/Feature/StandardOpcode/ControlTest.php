<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Execution\UnreachableException;
use Oatmael\WasmPhp\Module;

test('block', function () {})->todo();

test('br', function () {})->todo();

test('br_if', function () {})->todo();

test('br_table', function () {})->todo();

test('call', function (Module $module) {
  $ret = $module->execute('call', []);
  expect($ret[0]->value)->toBe(1);
})
  ->with([
    'callModule' => fn() => wat2module(<<<WAT
      (module
        (func \$one (param) (result i32)
          (i32.const 1)
        )
        (func (export "call") (param) (result i32)
          (call \$one)
        )
      )
      WAT)
  ]);

test('call_indirect', function () {})->todo();

test('drop', function () {})->todo();

// End is implied in most other tests, consider a more elegant way to test this
test('end', function () {})->todo();

test('else', function () {})->todo();

test('if', function () {})->todo();

test('return', function () {})->todo();

test('loop', function () {})->todo();

// Nop doesn't do anything. I don't even know how you'd test it
// TODO: potentially add a program counter to the store and test that it's incremented
test('nop', function () {})->todo();

test('select', function () {})->todo();

test('select_t', function () {})->todo();

test('unreachable', function (Module $module) {
  $this->expectException(UnreachableException::class);
  $module->execute('unreachable', []);
})->with([
  'unreachableModule' => fn() => wat2module(<<<WAT
    (module
      (func (export "unreachable") (result i32)
        (unreachable)
      )
    )
    WAT)
]);
