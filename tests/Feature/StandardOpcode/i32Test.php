<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Exception\BadIntegerCastException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;

test('i32_add', function (Module $module) {
  $ret = $module->execute('i32_add', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(85);

  $ret = $module->execute('i32_add', [new I32(0b11111111111111111111111111111111), new I32(1)]);
  expect($ret[0]->getValue())->toBe(0b0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_add") (param i32) (param i32) (result i32)
        (i32.add (local.get 0) (local.get 1))
      )
    )
    WAT)
  ]);

test('i32_and', function (Module $module) {
  $ret = $module->execute('i32_and', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(42 & 43);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_and") (param i32) (param i32) (result i32)
        (i32.and (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_clz', function (Module $module) {
  $ret = $module->execute('i32_clz', [new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(28);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_clz") (param i32) (result i32)
        (i32.clz (local.get 0))
      )
    )
    WAT)
]);

test('i32_const', function (Module $module) {
  $ret = $module->execute('i32_const', []);
  expect($ret[0]->getValue())->toBe(42);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_const") (result i32)
        (i32.const 42)
      )
    )
    WAT)
]);

test('i32_ctz', function (Module $module) {
  $ret = $module->execute('i32_ctz', [new I32(0b1000)]);
  expect($ret[0]->getValue())->toBe(3);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_ctz") (param i32) (result i32)
        (i32.ctz (local.get 0))
      )
    )
    WAT)
]);

test('i32_div_s', function (Module $module) {
  $ret = $module->execute('i32_div_s', [new I32(6), new I32(42)]);
  expect($ret[0]->getValue())->toBe(7);

  $ret = $module->execute('i32_div_s', [new I32(-6), new I32(-42)]);
  expect($ret[0]->getValue())->toBe(7);

  $ret = $module->execute('i32_div_s', [new I32(6), new I32(-42)]);
  expect($ret[0]->getValue())->toBe(-7);

  $ret = $module->execute('i32_div_s', [new I32(-6), new I32(42)]);
  expect($ret[0]->getValue())->toBe(-7);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_div_s") (param i32) (param i32) (result i32)
        (i32.div_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_div_u', function (Module $module) {
  $ret = $module->execute('i32_div_u', [new I32(6), new I32(42)]);
  expect($ret[0]->getValue())->toBe(7);

  $ret = $module->execute('i32_div_u', [new I32(-6), new I32(-42)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_div_u', [new I32(6), new I32(-42)]);
  expect($ret[0]->getValue())->toBe(715827875);

  $ret = $module->execute('i32_div_u', [new I32(-6), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_div_u") (param i32) (param i32) (result i32)
        (i32.div_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_eq', function (Module $module) {
  $ret = $module->execute('i32_eq', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_eq', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_eq") (param i32) (param i32) (result i32)
        (i32.eq (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_eqz', function (Module $module) {
  $ret = $module->execute('i32_eqz', [new I32(0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_eqz', [new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_eqz") (param i32) (result i32)
        (i32.eqz (local.get 0))
      )
    )
    WAT)
]);

test('i32_extend16_s', function (Module $module) {
  $ret = $module->execute('i32_extend16_s', [new I32(0b1010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b11111111111111111010101010101010);

  $ret = $module->execute('i32_extend16_s', [new I32(0b0101010101010101)]);
  expect($ret[0]->getValue())->toBe(0b0101010101010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_extend16_s") (param i32) (result i32)
        (i32.extend16_s (local.get 0))
      )
    )
    WAT)
]);

test('i32_extend8_s', function (Module $module) {
  $ret = $module->execute('i32_extend8_s', [new I32(0b10101010)]);
  expect($ret[0]->getValue())->toBe(0b11111111111111111111111110101010);

  $ret = $module->execute('i32_extend8_s', [new I32(0b01010101)]);
  expect($ret[0]->getValue())->toBe(0b01010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_extend8_s") (param i32) (result i32)
        (i32.extend8_s (local.get 0))
      )
    )
    WAT)
]);

test('i32_ge_s', function (Module $module) {
  $ret = $module->execute('i32_ge_s', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_ge_s', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_ge_s', [new I32(-43), new I32(42)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_ge_s', [new I32(42), new I32(-43)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_ge_s") (param i32) (param i32) (result i32)
        (i32.ge_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_ge_u', function (Module $module) {
  $ret = $module->execute('i32_ge_u', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_ge_u', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_ge_u', [new I32(-43), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_ge_u', [new I32(42), new I32(-43)]);
  expect($ret[0]->getValue())->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_ge_u") (param i32) (param i32) (result i32)
        (i32.ge_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_gt_s', function (Module $module) {
  $ret = $module->execute('i32_gt_s', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_gt_s', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_gt_s', [new I32(-43), new I32(42)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_gt_s', [new I32(42), new I32(-43)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_gt_s") (param i32) (param i32) (result i32)
        (i32.gt_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_gt_u', function (Module $module) {
  $ret = $module->execute('i32_gt_u', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_gt_u', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_gt_u', [new I32(-43), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_gt_u', [new I32(42), new I32(-43)]);
  expect($ret[0]->getValue())->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_gt_u") (param i32) (param i32) (result i32)
        (i32.gt_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_le_s', function (Module $module) {
  $ret = $module->execute('i32_le_s', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_le_s', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_le_s', [new I32(-43), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_le_s', [new I32(42), new I32(-43)]);
  expect($ret[0]->getValue())->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_le_s") (param i32) (param i32) (result i32)
        (i32.le_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_le_u', function (Module $module) {
  $ret = $module->execute('i32_le_u', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_le_u', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_le_u', [new I32(-43), new I32(42)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_le_u', [new I32(42), new I32(-43)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_le_u") (param i32) (param i32) (result i32)
        (i32.le_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_load', function (Module $module) {
  $ret = $module->execute('i32_load', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(43);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "i32_load") (param i32) (param i32) (result i32)
        (i32.store (local.get 0) (local.get 1))
        (i32.load (local.get 0))
      )
    )
    WAT)
]);

test('i32_load16_s', function (Module $module) {
  $ret = $module->execute('i32_load16_s', [new I32(0), new I32(0b10101010101010101010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b11111111111111111010101010101010);

  $ret = $module->execute('i32_load16_s', [new I32(0), new I32(0b01010101010101010101010101010101)]);
  expect($ret[0]->getValue())->toBe(0b0101010101010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "i32_load16_s") (param i32) (param i32) (result i32)
        (i32.store (local.get 0) (local.get 1))
        (i32.load16_s (local.get 0))
      )
    )
    WAT)
]);

test('i32_load16_u', function (Module $module) {
  $ret = $module->execute('i32_load16_u', [new I32(0), new I32(0b10101010101010101010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b1010101010101010);

  $ret = $module->execute('i32_load16_u', [new I32(0), new I32(0b01010101010101010101010101010101)]);
  expect($ret[0]->getValue())->toBe(0b0101010101010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "i32_load16_u") (param i32) (param i32) (result i32)
        (i32.store (local.get 0) (local.get 1))
        (i32.load16_u (local.get 0))
      )
    )
    WAT)
]);

test('i32_load8_s', function (Module $module) {
  $ret = $module->execute('i32_load8_s', [new I32(0), new I32(0b10101010101010101010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b11111111111111111111111110101010);

  $ret = $module->execute('i32_load8_s', [new I32(0), new I32(0b01010101010101010101010101010101)]);
  expect($ret[0]->getValue())->toBe(0b01010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "i32_load8_s") (param i32) (param i32) (result i32)
        (i32.store (local.get 0) (local.get 1))
        (i32.load8_s (local.get 0))
      )
    )
    WAT)
]);

test('i32_load8_u', function (Module $module) {
  $ret = $module->execute('i32_load8_u', [new I32(0), new I32(0b10101010101010101010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b10101010);

  $ret = $module->execute('i32_load8_u', [new I32(0), new I32(0b01010101010101010101010101010101)]);
  expect($ret[0]->getValue())->toBe(0b01010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "i32_load8_u") (param i32) (param i32) (result i32)
        (i32.store (local.get 0) (local.get 1))
        (i32.load8_u (local.get 0))
      )
    )
    WAT)
]);

test('i32_lt_s', function (Module $module) {
  $ret = $module->execute('i32_lt_s', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_lt_s', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_lt_s', [new I32(-43), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_lt_s', [new I32(42), new I32(-43)]);
  expect($ret[0]->getValue())->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_lt_s") (param i32) (param i32) (result i32)
        (i32.lt_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_lt_u', function (Module $module) {
  $ret = $module->execute('i32_lt_u', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_lt_u', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('i32_lt_u', [new I32(-43), new I32(42)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_lt_u', [new I32(42), new I32(-43)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_lt_u") (param i32) (param i32) (result i32)
        (i32.lt_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_mul', function (Module $module) {
  $ret = $module->execute('i32_mul', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(1806);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_mul") (param i32) (param i32) (result i32)
        (i32.mul (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_ne', function (Module $module) {
  $ret = $module->execute('i32_ne', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('i32_ne', [new I32(42), new I32(42)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_ne") (param i32) (param i32) (result i32)
        (i32.ne (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_or', function (Module $module) {
  $ret = $module->execute('i32_or', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(42 | 43);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_or") (param i32) (param i32) (result i32)
        (i32.or (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_popcnt', function (Module $module) {
  $ret = $module->execute('i32_popcnt', [new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(2);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_popcnt") (param i32) (result i32)
        (i32.popcnt (local.get 0))
      )
    )
    WAT)
]);

test('i32_reinterpret_f32', function (Module $module) {
  $ret = $module->execute('i32_reinterpret_f32', [new F32(3.14)]);
  expect($ret[0]->getValue())->toBe(1078523331);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_reinterpret_f32") (param f32) (result i32)
        (i32.reinterpret_f32 (local.get 0))
      )
    )
    WAT)
]);

test('i32_rem_s', function (Module $module) {
  $ret = $module->execute('i32_rem_s', [new I32(42), new I32(6)]);
  expect($ret[0]->getValue())->toBe(6);

  $ret = $module->execute('i32_rem_s', [new I32(-42), new I32(-6)]);
  expect($ret[0]->getValue())->toBe(-6);

  $ret = $module->execute('i32_rem_s', [new I32(-42), new I32(6)]);
  expect($ret[0]->getValue())->toBe(6);

  $ret = $module->execute('i32_rem_s', [new I32(42), new I32(-6)]);
  expect($ret[0]->getValue())->toBe(-6);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_rem_s") (param i32) (param i32) (result i32)
        (i32.rem_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_rem_u', function (Module $module) {
  $ret = $module->execute('i32_rem_u', [new I32(42), new I32(6)]);
  expect($ret[0]->getValue())->toBe(6);

  $ret = $module->execute('i32_rem_u', [new I32(-42), new I32(-6)]);
  expect($ret[0]->getValue())->toBe(36);

  $ret = $module->execute('i32_rem_u', [new I32(-42), new I32(6)]);
  expect($ret[0]->getValue())->toBe(6);

  $ret = $module->execute('i32_rem_u', [new I32(42), new I32(-6)]);
  expect($ret[0]->getValue())->toBe(40);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_rem_u") (param i32) (param i32) (result i32)
        (i32.rem_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_rotl', function (Module $module) {
  $ret = $module->execute('i32_rotl', [new I32(1), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b10100);

  $ret = $module->execute('i32_rotl', [new I32(2), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b101000);

  $ret = $module->execute('i32_rotl', [new I32(1), new I32(0b10101010101010101010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b01010101010101010101010101010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_rotl") (param i32) (param i32) (result i32)
        (i32.rotl (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_rotr', function (Module $module) {
  $ret = $module->execute('i32_rotr', [new I32(1), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b101);

  $ret = $module->execute('i32_rotr', [new I32(2), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b10000000000000000000000000000010);

  $ret = $module->execute('i32_rotr', [new I32(1), new I32(0b10101010101010101010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b01010101010101010101010101010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_rotr") (param i32) (param i32) (result i32)
        (i32.rotr (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_shl', function (Module $module) {
  $ret = $module->execute('i32_shl', [new I32(1), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b10100);

  $ret = $module->execute('i32_shl', [new I32(2), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b101000);

  $ret = $module->execute('i32_shl', [new I32(1), new I32(0b10101010101010101010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b01010101010101010101010101010100);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_shl") (param i32) (param i32) (result i32)
        (i32.shl (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_shr_s', function (Module $module) {
  $ret = $module->execute('i32_shr_s', [new I32(1), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b101);

  $ret = $module->execute('i32_shr_s', [new I32(2), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b10);

  $ret = $module->execute('i32_shr_s', [new I32(1), new I32(0b10101010101010101010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b11010101010101010101010101010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_shr_s") (param i32) (param i32) (result i32)
        (i32.shr_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_shr_u', function (Module $module) {
  $ret = $module->execute('i32_shr_u', [new I32(1), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b101);

  $ret = $module->execute('i32_shr_u', [new I32(2), new I32(0b1010)]);
  expect($ret[0]->getValue())->toBe(0b10);

  $ret = $module->execute('i32_shr_u', [new I32(1), new I32(0b10101010101010101010101010101010)]);
  expect($ret[0]->getValue())->toBe(0b01010101010101010101010101010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_shr_u") (param i32) (param i32) (result i32)
        (i32.shr_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_store', function (Module $module) {
  $module->execute('i32_store', [new I32(42), new I32(0b11111111111111111010101001010101)]);
  $memory = $module->getMemory();
  expect($memory->data[42])->toBe(0b01010101);
  expect($memory->data[43])->toBe(0b10101010);
  expect($memory->data[44])->toBe(0b11111111);
  expect($memory->data[45])->toBe(0b11111111);
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

test('i32_store16', function (Module $module) {
  $module->execute('i32_store16', [new I32(42), new I32(0b11111111111111111010101001010101)]);
  $memory = $module->getMemory();
  expect($memory->data[42])->toBe(0b01010101);
  expect($memory->data[43])->toBe(0b10101010);
  expect($memory->data[44])->not()->toBe(0b11111111);
  expect($memory->data[45])->not()->toBe(0b11111111);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "i32_store16") (param i32) (param i32) (result)
        (i32.store16 (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_store8', function (Module $module) {
  $module->execute('i32_store8', [new I32(42), new I32(0b11111111111111111010101001010101)]);
  $memory = $module->getMemory();
  expect($memory->data[42])->toBe(0b01010101);
  expect($memory->data[43])->not()->toBe(0b10101010);
  expect($memory->data[44])->not()->toBe(0b11111111);
  expect($memory->data[45])->not()->toBe(0b11111111);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "i32_store8") (param i32) (param i32) (result)
        (i32.store8 (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_sub', function (Module $module) {
  // The stack is reversed, so the first value is the second operand
  $ret = $module->execute('i32_sub', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_sub") (param i32) (param i32) (result i32)
        (i32.sub (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_trunc_f32_s', function (Module $module) {
  $ret = $module->execute('i32_trunc_f32_s', [new F32(3.14)]);
  expect($ret[0]->getValue())->toBe(3);

  $ret = $module->execute('i32_trunc_f32_s', [new F32(-3.14)]);
  expect($ret[0]->getValue())->toBe(-3);

  expect(fn() => $module->execute('i32_trunc_f32_s', [new F32(NAN)]))->toThrow(BadIntegerCastException::class);
  expect(fn() => $module->execute('i32_trunc_f32_s', [new F32(INF)]))->toThrow(BadIntegerCastException::class);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_trunc_f32_s") (param f32) (result i32)
        (i32.trunc_f32_s (local.get 0))
      )
    )
    WAT)
]);

test('i32_trunc_f32_u', function (Module $module) {
  $ret = $module->execute('i32_trunc_f32_u', [new F32(3.14)]);
  expect($ret[0]->getValue())->toBe(3);

  expect(fn() => $module->execute('i32_trunc_f32_u', [new F32(NAN)]))->toThrow(BadIntegerCastException::class);
  expect(fn() => $module->execute('i32_trunc_f32_u', [new F32(INF)]))->toThrow(BadIntegerCastException::class);
  expect(fn() => $module->execute('i32_trunc_f32_u', [new F32(-3.14)]))->toThrow(BadIntegerCastException::class);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_trunc_f32_u") (param f32) (result i32)
        (i32.trunc_f32_u (local.get 0))
      )
    )
    WAT)
]);

test('i32_trunc_f64_s', function (Module $module) {
  $ret = $module->execute('i32_trunc_f64_s', [new F64(3.14)]);
  expect($ret[0]->getValue())->toBe(3);

  $ret = $module->execute('i32_trunc_f64_s', [new F64(-3.14)]);
  expect($ret[0]->getValue())->toBe(-3);

  expect(fn() => $module->execute('i32_trunc_f64_s', [new F64(NAN)]))->toThrow(BadIntegerCastException::class);
  expect(fn() => $module->execute('i32_trunc_f64_s', [new F64(INF)]))->toThrow(BadIntegerCastException::class);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_trunc_f64_s") (param f64) (result i32)
        (i32.trunc_f64_s (local.get 0))
      )
    )
    WAT)
]);

test('i32_trunc_f64_u', function (Module $module) {
  $ret = $module->execute('i32_trunc_f64_u', [new F64(3.14)]);
  expect($ret[0]->getValue())->toBe(3);

  expect(fn() => $module->execute('i32_trunc_f64_u', [new F64(NAN)]))->toThrow(BadIntegerCastException::class);
  expect(fn() => $module->execute('i32_trunc_f64_u', [new F64(INF)]))->toThrow(BadIntegerCastException::class);
  expect(fn() => $module->execute('i32_trunc_f64_u', [new F64(-3.14)]))->toThrow(BadIntegerCastException::class);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_trunc_f64_u") (param f64) (result i32)
        (i32.trunc_f64_u (local.get 0))
      )
    )
    WAT)
]);

test('i32_wrap_i64', function (Module $module) {
  $ret = $module->execute('i32_wrap_i64', [
    new I64(0b0111111111111111101010100101010111111111111111111010101001010101)
  ]);
  expect($ret[0]->getValue())->toBe(0b11111111111111111010101001010101);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_wrap_i64") (param i64) (result i32)
        (i32.wrap_i64 (local.get 0))
      )
    )
    WAT)
]);

test('i32_xor', function (Module $module) {
  $ret = $module->execute('i32_xor', [new I32(42), new I32(43)]);
  expect($ret[0]->getValue())->toBe(42 ^ 43);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_xor") (param i32) (param i32) (result i32)
        (i32.xor (local.get 0) (local.get 1))
      )
    )
    WAT)
]);
