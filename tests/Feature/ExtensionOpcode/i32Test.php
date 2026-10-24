<?php

namespace Tests\Feature\ExtensionOpcode;

use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;

test('i32_trunc_sat_f32_s', function () {
  expect(wasmOp('i32.trunc_sat_f32_s', [new F32(-1.9)], 'i32')->getValue())->toBe(-1);
  expect(wasmOp('i32.trunc_sat_f32_s', [new F32(3e9)], 'i32')->getValue())->toBe(2147483647);
  expect(wasmOp('i32.trunc_sat_f32_s', [new F32(-3e9)], 'i32')->getValue())->toBe(-2147483648);
  expect(wasmOp('i32.trunc_sat_f32_s', [new F32(NAN)], 'i32')->getValue())->toBe(0);
});

test('i32_trunc_sat_f32_u', function () {
  expect(wasmOp('i32.trunc_sat_f32_u', [new F32(3e9)], 'i32')->toUnsigned()->value)->toBe(3000000000);
  expect(wasmOp('i32.trunc_sat_f32_u', [new F32(5e9)], 'i32')->toUnsigned()->value)->toBe(0xFFFFFFFF);
  expect(wasmOp('i32.trunc_sat_f32_u', [new F32(-1.0)], 'i32')->getValue())->toBe(0);
});

test('i32_trunc_sat_f64_s', function () {
  expect(wasmOp('i32.trunc_sat_f64_s', [new F64(2147483647.9)], 'i32')->getValue())->toBe(2147483647);
  expect(wasmOp('i32.trunc_sat_f64_s', [new F64(-INF)], 'i32')->getValue())->toBe(-2147483648);
});

test('i32_trunc_sat_f64_u', function () {
  expect(wasmOp('i32.trunc_sat_f64_u', [new F64(4294967295.9)], 'i32')->toUnsigned()->value)->toBe(0xFFFFFFFF);
  expect(wasmOp('i32.trunc_sat_f64_u', [new F64(NAN)], 'i32')->getValue())->toBe(0);
});
