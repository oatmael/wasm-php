<?php

namespace Tests\Feature\ExtensionOpcode;

use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;

test('i64_trunc_sat_f32_s', function () {
  expect(wasmOp('i64.trunc_sat_f32_s', [new F32(-1.9)], 'i64')->getValue())->toBe(-1);
  expect(wasmOp('i64.trunc_sat_f32_s', [new F32(1e19)], 'i64')->getValue())->toBe(PHP_INT_MAX);
  expect(wasmOp('i64.trunc_sat_f32_s', [new F32(-1e19)], 'i64')->getValue())->toBe(PHP_INT_MIN);
  expect(wasmOp('i64.trunc_sat_f32_s', [new F32(NAN)], 'i64')->getValue())->toBe(0);
});

test('i64_trunc_sat_f32_u', function () {
  // The maximum unsigned i64 wraps to -1 as a PHP int
  expect(wasmOp('i64.trunc_sat_f32_u', [new F32(1e20)], 'i64')->getValue())->toBe(-1);
  expect(wasmOp('i64.trunc_sat_f32_u', [new F32(-5.0)], 'i64')->getValue())->toBe(0);
});

test('i64_trunc_sat_f64_s', function () {
  expect(wasmOp('i64.trunc_sat_f64_s', [new F64(-9.3e18)], 'i64')->getValue())->toBe(PHP_INT_MIN);
  expect(wasmOp('i64.trunc_sat_f64_s', [new F64(123.9)], 'i64')->getValue())->toBe(123);
});

test('i64_trunc_sat_f64_u', function () {
  expect(wasmOp('i64.trunc_sat_f64_u', [new F64(9223372036854775808.0)], 'i64')->toBigInteger(unsigned: true)->toBase(10))->toBe('9223372036854775808');
  expect(wasmOp('i64.trunc_sat_f64_u', [new F64(INF)], 'i64')->getValue())->toBe(-1);
});
