<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;

function f64Op(string $instruction, array $args, string $result = 'f64'): int|float
{
  $args = array_map(fn ($arg) => is_float($arg) ? new F64($arg) : $arg, $args);
  return wasmOp($instruction, $args, $result)->getValue();
}

test('f64_abs', function () {
  expect(f64Op('f64.abs', [-1.5]))->toBe(1.5);
  expect(f64Op('f64.abs', [1.5]))->toBe(1.5);
  expect(f64Op('f64.abs', [-INF]))->toBe(INF);

  // Only the sign bit changes, so NaN payloads are preserved
  $ret = wasmOp('f64.abs', [F64::fromBits(PHP_INT_MIN | 0x7FF4000000000000)], 'f64');
  expect($ret->getBits())->toBe(0x7FF4000000000000);
});

test('f64_add', function () {
  expect(f64Op('f64.add', [1.5, 2.25]))->toBe(3.75);
  expect(f64Op('f64.add', [0.1, 0.2]))->toBe(0.1 + 0.2);
  expect(f64Op('f64.add', [INF, -INF]))->toBeNan();
});

test('f64_ceil', function () {
  expect(f64Op('f64.ceil', [1.1]))->toBe(2.0);
  expect(f64Op('f64.ceil', [-1.9]))->toBe(-1.0);
  expect(fdiv(1, f64Op('f64.ceil', [-0.5])))->toBe(-INF);
});

test('f64_const', function (Module $module) {
  $ret = $module->execute('f64_const', []);
  expect($ret[0]->getValue())->toBe(-1.25);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "f64_const") (result f64)
        (f64.const -1.25)
      )
    )
    WAT)
]);

test('f64_convert_i32_s', function () {
  expect(f64Op('f64.convert_i32_s', [new I32(-5)]))->toBe(-5.0);
  expect(f64Op('f64.convert_i32_s', [new I32(0x80000000)]))->toBe(-2147483648.0);
});

test('f64_convert_i32_u', function () {
  expect(f64Op('f64.convert_i32_u', [new I32(-1)]))->toBe(4294967295.0);
});

test('f64_convert_i64_s', function () {
  expect(f64Op('f64.convert_i64_s', [new I64(-5)]))->toBe(-5.0);
  expect(f64Op('f64.convert_i64_s', [new I64(PHP_INT_MIN)]))->toBe(-9223372036854775808.0);
});

test('f64_convert_i64_u', function () {
  expect(f64Op('f64.convert_i64_u', [new I64(-1)]))->toBe(18446744073709551616.0);
  // 2^63 + 2^11 + 1 rounds up, which a naive conversion through the signed value gets wrong
  expect(f64Op('f64.convert_i64_u', [new I64(PHP_INT_MIN | 0x801)]))->toBe(9223372036854777856.0);
});

test('f64_copysign', function () {
  expect(f64Op('f64.copysign', [1.5, -0.0]))->toBe(-1.5);
  expect(f64Op('f64.copysign', [-1.5, 2.0]))->toBe(1.5);
});

test('f64_div', function () {
  expect(f64Op('f64.div', [7.0, 2.0]))->toBe(3.5);
  expect(f64Op('f64.div', [1.0, 0.0]))->toBe(INF);
  expect(f64Op('f64.div', [1.0, -0.0]))->toBe(-INF);
  expect(f64Op('f64.div', [0.0, 0.0]))->toBeNan();
});

test('f64_eq', function () {
  expect(f64Op('f64.eq', [1.5, 1.5], 'i32'))->toBe(1);
  expect(f64Op('f64.eq', [0.0, -0.0], 'i32'))->toBe(1);
  expect(f64Op('f64.eq', [NAN, NAN], 'i32'))->toBe(0);
});

test('f64_floor', function () {
  expect(f64Op('f64.floor', [1.9]))->toBe(1.0);
  expect(f64Op('f64.floor', [-1.1]))->toBe(-2.0);
});

test('f64_ge', function () {
  expect(f64Op('f64.ge', [2.0, 1.0], 'i32'))->toBe(1);
  expect(f64Op('f64.ge', [1.0, 1.0], 'i32'))->toBe(1);
  expect(f64Op('f64.ge', [NAN, 1.0], 'i32'))->toBe(0);
});

test('f64_gt', function () {
  expect(f64Op('f64.gt', [2.0, 1.0], 'i32'))->toBe(1);
  expect(f64Op('f64.gt', [1.0, 1.0], 'i32'))->toBe(0);
});

test('f64_le', function () {
  expect(f64Op('f64.le', [1.0, 1.0], 'i32'))->toBe(1);
  expect(f64Op('f64.le', [2.0, 1.0], 'i32'))->toBe(0);
});

test('f64_load', function () {
  $module = wat2module(<<<'WAT'
    (module
      (memory 1)
      (data (i32.const 8) "\00\00\00\00\00\00\f8\3f")
      (func (export "f") (param i32) (result f64)
        (f64.load (local.get 0))
      )
    )
    WAT);

  expect($module->execute('f', [new I32(8)])[0]->getValue())->toBe(1.5);
  expect(fn () => $module->execute('f', [new I32(65535)]))->toThrow(\Exception::class, 'out of bounds memory access');
});

test('f64_lt', function () {
  expect(f64Op('f64.lt', [1.0, 2.0], 'i32'))->toBe(1);
  expect(f64Op('f64.lt', [-0.0, 0.0], 'i32'))->toBe(0);
});

test('f64_max', function () {
  expect(f64Op('f64.max', [1.0, 2.0]))->toBe(2.0);
  expect(fdiv(1, f64Op('f64.max', [-0.0, 0.0])))->toBe(INF);
  expect(f64Op('f64.max', [NAN, 2.0]))->toBeNan();
});

test('f64_min', function () {
  expect(f64Op('f64.min', [1.0, 2.0]))->toBe(1.0);
  expect(fdiv(1, f64Op('f64.min', [0.0, -0.0])))->toBe(-INF);
  expect(f64Op('f64.min', [1.0, NAN]))->toBeNan();
});

test('f64_mul', function () {
  expect(f64Op('f64.mul', [1.5, -2.0]))->toBe(-3.0);
  expect(f64Op('f64.mul', [INF, 0.0]))->toBeNan();
});

test('f64_ne', function () {
  expect(f64Op('f64.ne', [1.0, 2.0], 'i32'))->toBe(1);
  expect(f64Op('f64.ne', [NAN, NAN], 'i32'))->toBe(1);
});

test('f64_nearest', function () {
  expect(f64Op('f64.nearest', [2.5]))->toBe(2.0);
  expect(f64Op('f64.nearest', [3.5]))->toBe(4.0);
  expect(f64Op('f64.nearest', [-1.6]))->toBe(-2.0);
  expect(fdiv(1, f64Op('f64.nearest', [-0.4])))->toBe(-INF);
});

test('f64_neg', function () {
  expect(f64Op('f64.neg', [1.5]))->toBe(-1.5);
  expect(fdiv(1, f64Op('f64.neg', [0.0])))->toBe(-INF);
});

test('f64_promote_f32', function () {
  expect(f64Op('f64.promote_f32', [new F32(1.5)]))->toBe(1.5);
  expect(f64Op('f64.promote_f32', [new F32(0.1)]))->toBe((new F32(0.1))->value);
});

test('f64_reinterpret_i64', function () {
  expect(f64Op('f64.reinterpret_i64', [new I64(0x3FF8000000000000)]))->toBe(1.5);
  expect(f64Op('f64.reinterpret_i64', [new I64(PHP_INT_MIN)]))->toBe(-0.0);
});

test('f64_sqrt', function () {
  expect(f64Op('f64.sqrt', [6.25]))->toBe(2.5);
  expect(f64Op('f64.sqrt', [-1.0]))->toBeNan();
});

test('f64_store', function () {
  $module = wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "f") (param i32 f64)
        (f64.store offset=1 (local.get 0) (local.get 1))
      )
    )
    WAT);

  $module->execute('f', [new I32(7), new F64(1.5)]);
  expect($module->getMemory()->read(8, 8))->toBe("\0\0\0\0\0\0\xf8\x3f");
  expect(fn () => $module->execute('f', [new I32(65528), new F64(1.5)]))->toThrow(\Exception::class, 'out of bounds memory access');
});

test('f64_sub', function () {
  expect(f64Op('f64.sub', [1.0, 2.5]))->toBe(-1.5);
});

test('f64_trunc', function () {
  expect(f64Op('f64.trunc', [1.9]))->toBe(1.0);
  expect(f64Op('f64.trunc', [-1.9]))->toBe(-1.0);
});
