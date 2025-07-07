<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;

test('f32_abs', function (Module $module) {
  $ret = $module->execute('f32_abs', [new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_abs', [new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_abs") (param f32) (result f32)
      (f32.abs (local.get 0))
    )
  )
  WAT),
]);

test('f32_add', function (Module $module) {
  $ret = $module->execute('f32_add', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(3.0);

  $ret = $module->execute('f32_add', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(-3.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_add") (param f32) (param f32) (result f32)
      (f32.add (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_ceil', function (Module $module) {
  $ret = $module->execute('f32_ceil', [new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_ceil', [new F32(1.1)]);
  expect($ret[0]->getValue())->toBe(2.0);

  $ret = $module->execute('f32_ceil', [new F32(1.9)]);
  expect($ret[0]->getValue())->toBe(2.0);

  $ret = $module->execute('f32_ceil', [new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(-1.0);

  $ret = $module->execute('f32_ceil', [new F32(-1.1)]);
  expect($ret[0]->getValue())->toBe(-1.0);

  $ret = $module->execute('f32_ceil', [new F32(-1.9)]);
  expect($ret[0]->getValue())->toBe(-1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_ceil") (param f32) (result f32)
      (f32.ceil (local.get 0))
    )
  )
  WAT),
]);

test('f32_const', function (Module $module) {
  $ret = $module->execute('f32_const', []);
  expect($ret[0]->getValue())->toBe(1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_const") (result f32)
      (f32.const 1.0)
    )
  )
  WAT),
]);

// TODO: I'm unsure about these convert functions, need to solidify some better test cases here
test('f32_convert_i32_s', function (Module $module) {
  $ret = $module->execute('f32_convert_i32_s', [new I32(1)]);
  expect($ret[0])->toBeInstanceOf(F32::class);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_convert_i32_s', [new I32(-1)]);
  expect($ret[0])->toBeInstanceOf(F32::class);
  expect($ret[0]->getValue())->toBe(-1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_convert_i32_s") (param i32) (result f32)
      (f32.convert_i32_s (local.get 0))
    )
  )
  WAT),
]);

test('f32_convert_i32_u', function (Module $module) {
  $ret = $module->execute('f32_convert_i32_u', [new I32(1)]);
  expect($ret[0])->toBeInstanceOf(F32::class);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_convert_i32_u', [new I32(-1)]);
  expect($ret[0])->toBeInstanceOf(F32::class);
  expect($ret[0]->getValue())->toBe(4294967296.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_convert_i32_u") (param i32) (result f32)
      (f32.convert_i32_u (local.get 0))
    )
  )
  WAT),
]);

test('f32_convert_i64_s', function (Module $module) {
  $ret = $module->execute('f32_convert_i64_s', [new I64(1)]);
  expect($ret[0])->toBeInstanceOf(F32::class);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_convert_i64_s', [new I64(-1)]);
  expect($ret[0])->toBeInstanceOf(F32::class);
  expect($ret[0]->getValue())->toBe(-1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_convert_i64_s") (param i64) (result f32)
      (f32.convert_i64_s (local.get 0))
    )
  )
  WAT),
]);

test('f32_convert_i64_u', function (Module $module) {
  $ret = $module->execute('f32_convert_i64_u', [new I64(1)]);
  expect($ret[0])->toBeInstanceOf(F32::class);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_convert_i64_u', [new I64(-1)]);
  expect($ret[0])->toBeInstanceOf(F32::class);
  expect($ret[0]->getValue())->toBe(18446744073709551616.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_convert_i64_u") (param i64) (result f32)
      (f32.convert_i64_u (local.get 0))
    )
  )
  WAT),
])
  ->todo('Not currently handling unsigned 64-bit integers correctly');

test('f32_copysign', function (Module $module) {
  $ret = $module->execute('f32_copysign', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_copysign', [new F32(-1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_copysign', [new F32(1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(-1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_copysign") (param f32) (param f32) (result f32)
      (f32.copysign (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_demote_f64', function (Module $module) {
  $ret = $module->execute('f32_demote_f64', [new F64(1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_demote_f64', [new F64(1.1)]);
  expect($ret[0]->getValue())->toBe(1.100000023841858);

  $ret = $module->execute('f32_demote_f64', [new F64(1.2)]);
  expect($ret[0]->getValue())->toBe(1.2000000476837158);

  $ret = $module->execute('f32_demote_f64', [new F64(NAN)]);
  expect($ret[0]->getValue())->toBeNan();

  $ret = $module->execute('f32_demote_f64', [new F64(INF)]);
  expect($ret[0]->getValue())->toBe(INF);

  $ret = $module->execute('f32_demote_f64', [new F64(-INF)]);
  expect($ret[0]->getValue())->toBe(-INF);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_demote_f64") (param f64) (result f32)
      (f32.demote_f64 (local.get 0))
    )
  )
  WAT),
]);

test('f32_div', function (Module $module) {
  $ret = $module->execute('f32_div', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(0.5);

  $ret = $module->execute('f32_div', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(0.5);

  $ret = $module->execute('f32_div', [new F32(1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(-0.5);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_div") (param f32) (param f32) (result f32)
      (f32.div (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_eq', function (Module $module) {
  $ret = $module->execute('f32_eq', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_eq', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_eq', [new F32(1.0), new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_eq', [new F32(1.0), new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_eq', [new F32(INF), new F32(INF)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_eq', [new F32(NAN), new F32(NAN)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_eq") (param f32) (param f32) (result i32)
      (f32.eq (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_floor', function (Module $module) {
  $ret = $module->execute('f32_floor', [new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_floor', [new F32(1.1)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_floor', [new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(-1.0);

  $ret = $module->execute('f32_floor', [new F32(-1.1)]);
  expect($ret[0]->getValue())->toBe(-2.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_floor") (param f32) (result f32)
      (f32.floor (local.get 0))
    )
  )
  WAT),
]);

test('f32_ge', function (Module $module) {
  $ret = $module->execute('f32_ge', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_ge', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_ge', [new F32(1.0), new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_ge', [new F32(1.0), new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(1);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_ge") (param f32) (param f32) (result i32)
      (f32.ge (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_gt', function (Module $module) {
  $ret = $module->execute('f32_gt', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_gt', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_gt', [new F32(1.0), new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_gt', [new F32(1.0), new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(1);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_gt") (param f32) (param f32) (result i32)
      (f32.gt (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_le', function (Module $module) {
  $ret = $module->execute('f32_le', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_le', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_le', [new F32(1.0), new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_le', [new F32(1.0), new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_le") (param f32) (param f32) (result i32)
      (f32.le (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_load', function (Module $module) {
  $ret = $module->execute('f32_load', [new I32(42), new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (memory 1)
    (func (export "f32_load") (param i32) (param f32) (result f32)
      (f32.store (local.get 0) (local.get 1))
      (f32.load (local.get 0))
    )
  )
  WAT),
]);

test('f32_lt', function (Module $module) {
  $ret = $module->execute('f32_lt', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_lt', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_lt', [new F32(1.0), new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_lt', [new F32(1.0), new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_lt") (param f32) (param f32) (result i32)
      (f32.lt (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_max', function (Module $module) {
  $ret = $module->execute('f32_max', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(2.0);

  $ret = $module->execute('f32_max', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(-1.0);

  $ret = $module->execute('f32_max', [new F32(1.0), new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_max") (param f32) (param f32) (result f32)
      (f32.max (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_min', function (Module $module) {
  $ret = $module->execute('f32_min', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_min', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(-2.0);

  $ret = $module->execute('f32_min', [new F32(1.0), new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(-1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_min") (param f32) (param f32) (result f32)
      (f32.min (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_mul', function (Module $module) {
  $ret = $module->execute('f32_mul', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(2.0);

  $ret = $module->execute('f32_mul', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(2.0);

  $ret = $module->execute('f32_mul', [new F32(1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(-2.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_mul") (param f32) (param f32) (result f32)
      (f32.mul (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_ne', function (Module $module) {
  $ret = $module->execute('f32_ne', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_ne', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(1);

  $ret = $module->execute('f32_ne', [new F32(1.0), new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_ne', [new F32(INF), new F32(INF)]);
  expect($ret[0]->getValue())->toBe(0);

  $ret = $module->execute('f32_ne', [new F32(NAN), new F32(NAN)]);
  expect($ret[0]->getValue())->toBe(1);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_ne") (param f32) (param f32) (result i32)
      (f32.ne (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_nearest', function (Module $module) {
  $ret = $module->execute('f32_nearest', [new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_nearest', [new F32(1.1)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_nearest', [new F32(1.9)]);
  expect($ret[0]->getValue())->toBe(2.0);

  $ret = $module->execute('f32_nearest', [new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(-1.0);

  $ret = $module->execute('f32_nearest', [new F32(-1.1)]);
  expect($ret[0]->getValue())->toBe(-1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_nearest") (param f32) (result f32)
      (f32.nearest (local.get 0))
    )
  )
  WAT),
]);

test('f32_neg', function (Module $module) {
  $ret = $module->execute('f32_neg', [new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(-1.0);

  $ret = $module->execute('f32_neg', [new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_neg") (param f32) (result f32)
      (f32.neg (local.get 0))
    )
  )
  WAT),
]);

test('f32_reinterpret_i32', function (Module $module) {
  // 0b01000000001000000000000000000000
  $ret = $module->execute('f32_reinterpret_i32', [new I32(1075838976)]);
  expect($ret[0]->getValue())->toBe(2.5);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_reinterpret_i32") (param i32) (result f32)
      (f32.reinterpret_i32 (local.get 0))
    )
  )
  WAT),
]);

test('f32_sqrt', function (Module $module) {
  $ret = $module->execute('f32_sqrt', [new F32(4.0)]);
  expect($ret[0]->getValue())->toBe(2.0);

  $ret = $module->execute('f32_sqrt', [new F32(0.0)]);
  expect($ret[0]->getValue())->toBe(0.0);

  $ret = $module->execute('f32_sqrt', [new F32(-1.0)]);
  expect($ret[0]->getValue())->toBeNan();
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_sqrt") (param f32) (result f32)
      (f32.sqrt (local.get 0))
    )
  )
  WAT),
]);

test('f32_store', function (Module $module) {
  $module->execute('f32_store', [new I32(42), new F32(2.5)]);
  $memory = $module->getMemory();
  expect($memory->data[42])->toBe(0b00000000);
  expect($memory->data[43])->toBe(0b00000000);
  expect($memory->data[44])->toBe(0b00100000);
  expect($memory->data[45])->toBe(0b01000000);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (memory 1)
    (func (export "f32_store") (param i32) (param f32) (result)
      (f32.store (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_sub', function (Module $module) {
  $ret = $module->execute('f32_sub', [new F32(1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(-1.0);

  $ret = $module->execute('f32_sub', [new F32(1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(3.0);

  $ret = $module->execute('f32_sub', [new F32(-1.0), new F32(2.0)]);
  expect($ret[0]->getValue())->toBe(-3.0);

  $ret = $module->execute('f32_sub', [new F32(-1.0), new F32(-2.0)]);
  expect($ret[0]->getValue())->toBe(1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_sub") (param f32) (param f32) (result f32)
      (f32.sub (local.get 0) (local.get 1))
    )
  )
  WAT),
]);

test('f32_trunc', function (Module $module) {
  $ret = $module->execute('f32_trunc', [new F32(1.0)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_trunc', [new F32(1.1)]);
  expect($ret[0]->getValue())->toBe(1.0);

  $ret = $module->execute('f32_trunc', [new F32(-1.0)]);
  expect($ret[0]->getValue())->toBe(-1.0);

  $ret = $module->execute('f32_trunc', [new F32(-1.1)]);
  expect($ret[0]->getValue())->toBe(-1.0);
})->with([
  'module' => wat2module(<<<WAT
  (module
    (func (export "f32_trunc") (param f32) (result f32)
      (f32.trunc (local.get 0))
    )
  )
  WAT),
]);