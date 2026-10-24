<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Module;

test('i64_add', function () {})->todo();

test('i64_and', function () {})->todo();

test('i64_clz', function () {})->todo();

test('i64_const', function (Module $module) {
  $ret = $module->execute('i64_const', []);
  expect($ret[0]->getValue())->toBe(PHP_INT_MIN);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i64_const") (result i64)
        (i64.const -9223372036854775808)
      )
    )
    WAT)
]);

test('i64_ctz', function () {})->todo();

test('i64_div_s', function () {})->todo();

test('i64_div_u', function () {})->todo();

test('i64_eq', function () {})->todo();

test('i64_eqz', function () {})->todo();

test('i64_extend16_s', function () {})->todo();

test('i64_extend32_s', function () {})->todo();

test('i64_extend8_s', function () {})->todo();

test('i64_extend_i32_s', function () {})->todo();

test('i64_extend_i32_u', function () {})->todo();

test('i64_ge_s', function () {})->todo();

test('i64_ge_u', function () {})->todo();

test('i64_gt_s', function () {})->todo();

test('i64_gt_u', function () {})->todo();

test('i64_le_s', function () {})->todo();

test('i64_le_u', function () {})->todo();

test('i64_load', function () {})->todo();

test('i64_load16_s', function () {})->todo();

test('i64_load16_u', function () {})->todo();

test('i64_load32_s', function () {})->todo();

test('i64_load32_u', function () {})->todo();

test('i64_load8_s', function () {})->todo();

test('i64_load8_u', function () {})->todo();

test('i64_lt_s', function () {})->todo();

test('i64_lt_u', function () {})->todo();

test('i64_mul', function () {})->todo();

test('i64_ne', function () {})->todo();

test('i64_or', function () {})->todo();

test('i64_popcnt', function () {})->todo();

test('i64_reinterpret_f64', function () {})->todo();

test('i64_rem_s', function () {})->todo();

test('i64_rem_u', function () {})->todo();

test('i64_rotl', function () {})->todo();

test('i64_rotr', function () {})->todo();

test('i64_shl', function () {})->todo();

test('i64_shr_s', function () {})->todo();

test('i64_shr_u', function () {})->todo();

test('i64_store', function () {})->todo();

test('i64_store16', function () {})->todo();

test('i64_store32', function () {})->todo();

test('i64_store8', function () {})->todo();

test('i64_sub', function () {})->todo();

test('i64_trunc_f32_s', function () {})->todo();

test('i64_trunc_f32_u', function () {})->todo();

test('i64_trunc_f64_s', function () {})->todo();

test('i64_trunc_f64_u', function () {})->todo();

test('i64_xor', function () {})->todo();
