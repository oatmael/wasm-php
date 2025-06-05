<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;

test('i32_add', function (Module $module) {
  $ret = $module->execute('i32_add', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(85);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_add") (param i32) (param i32) (result i32)
        (i32.add (local.get 0) (local.get 1))
      )
    )
    WAT)
  ]);

test('i32_and', function () {})->todo();

test('i32_clz', function () {})->todo();

test('i32_const', function (Module $module) {
  $ret = $module->execute('i32_const', []);
  expect($ret[0]->value)->toBe(42);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_const") (result i32)
        (i32.const 42)
      )
    )
    WAT)
]);

test('i32_ctz', function () {})->todo();

test('i32_div_s', function () {})->todo();

test('i32_div_u', function () {})->todo();

test('i32_eq', function () {})->todo();

test('i32_eqz', function () {})->todo();

test('i32_extend16_s', function () {})->todo();

test('i32_extend8_s', function () {})->todo();

test('i32_ge_s', function () {})->todo();

test('i32_ge_u', function () {})->todo();

test('i32_gt_s', function () {})->todo();

test('i32_gt_u', function () {})->todo();

test('i32_le_s', function () {})->todo();

test('i32_le_u', function () {})->todo();

test('i32_load', function () {})->todo();

test('i32_load16_s', function () {})->todo();

test('i32_load16_u', function () {})->todo();

test('i32_load8_s', function () {})->todo();

test('i32_load8_u', function () {})->todo();

test('i32_lt_s', function () {})->todo();

test('i32_lt_u', function () {})->todo();

test('i32_mul', function () {})->todo();

test('i32_ne', function () {})->todo();

test('i32_or', function () {})->todo();

test('i32_popcnt', function () {})->todo();

test('i32_reinterpret_f32', function () {})->todo();

test('i32_rem_s', function () {})->todo();

test('i32_rem_u', function () {})->todo();

test('i32_rotl', function () {})->todo();

test('i32_rotr', function () {})->todo();

test('i32_shl', function () {})->todo();

test('i32_shr_s', function () {})->todo();

test('i32_shr_u', function () {})->todo();

test('i32_store', function (Module $module) {
  $module->execute('i32_store', [new I32(42), new I32(43)]);
  $memory = $module->getMemory();
  expect($memory->data[42])->toBe(43);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "i32_store") (param i32) (param i32) (result)
        (i32.store (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_store16', function () {})->todo();

test('i32_store8', function () {})->todo();

test('i32_sub', function (Module $module) {
  // The stack is reversed, so the first value is the second operand
  $ret = $module->execute('i32_sub', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_sub") (param i32) (param i32) (result i32)
        (i32.sub (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_trunc_f32_s', function () {})->todo();

test('i32_trunc_f32_u', function () {})->todo();

test('i32_trunc_f64_s', function () {})->todo();

test('i32_trunc_f64_u', function () {})->todo();

test('i32_wrap_i64', function () {})->todo();

test('i32_xor', function () {})->todo();
