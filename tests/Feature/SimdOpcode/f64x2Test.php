<?php

namespace Tests\Feature\SimdOpcode;

use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Util\Shape;

test('f64x2_abs', function () {
  expect(simdOp('f64x2.abs', [v128(Shape::F64x2, [1.5, -2.25])], Shape::F64x2))->toBe([1.5, 2.25]);
});

test('f64x2_add', function () {
  expect(simdOp('f64x2.add', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([3.5, -1.75]);
});

test('f64x2_ceil', function () {
  expect(simdOp('f64x2.ceil', [v128(Shape::F64x2, [1.5, -1.5])], Shape::F64x2))->toBe([2.0, -1.0]);
});

test('f64x2_convert_low_i32x4_s', function () {
  expect(simdOp('f64x2.convert_low_i32x4_s', [v128(Shape::I32x4, [-1, 2, 3, 4])], Shape::F64x2))->toBe([-1.0, 2.0]);
});

test('f64x2_convert_low_i32x4_u', function () {
  expect(simdOp('f64x2.convert_low_i32x4_u', [v128(Shape::I32x4, [-1, 2, 3, 4])], Shape::F64x2))->toBe([4294967295.0, 2.0]);
});

test('f64x2_div', function () {
  expect(simdOp('f64x2.div', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([0.75, -4.5]);
});

test('f64x2_eq', function () {
  expect(simdOp('f64x2.eq', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [1.5, 0.5])], Shape::I64x2))->toBe([-1, 0]);
});

test('f64x2_extract_lane', function () {
  expect(wasmOp('f64x2.extract_lane 1', [v128(Shape::F64x2, [1.5, -2.25])], 'f64')->getValue())->toBe(-2.25);
});

test('f64x2_floor', function () {
  expect(simdOp('f64x2.floor', [v128(Shape::F64x2, [1.5, -1.5])], Shape::F64x2))->toBe([1.0, -2.0]);
});

test('f64x2_ge', function () {
  expect(simdOp('f64x2.ge', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [1.5, 0.5])], Shape::I64x2))->toBe([-1, 0]);
});

test('f64x2_gt', function () {
  expect(simdOp('f64x2.gt', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [1.5, 0.5])], Shape::I64x2))->toBe([0, 0]);
});

test('f64x2_le', function () {
  expect(simdOp('f64x2.le', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [1.5, 0.5])], Shape::I64x2))->toBe([-1, -1]);
});

test('f64x2_lt', function () {
  expect(simdOp('f64x2.lt', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [1.5, 0.5])], Shape::I64x2))->toBe([0, -1]);
});

test('f64x2_max', function () {
  expect(simdOp('f64x2.max', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([2.0, 0.5]);
});

test('f64x2_min', function () {
  expect(simdOp('f64x2.min', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([1.5, -2.25]);
});

test('f64x2_mul', function () {
  expect(simdOp('f64x2.mul', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([3.0, -1.125]);
});

test('f64x2_ne', function () {
  expect(simdOp('f64x2.ne', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [1.5, 0.5])], Shape::I64x2))->toBe([0, -1]);
});

test('f64x2_nearest', function () {
  // Ties round to even
  expect(simdOp('f64x2.nearest', [v128(Shape::F64x2, [1.5, -1.5])], Shape::F64x2))->toBe([2.0, -2.0]);
});

test('f64x2_neg', function () {
  expect(simdOp('f64x2.neg', [v128(Shape::F64x2, [1.5, -2.25])], Shape::F64x2))->toBe([-1.5, 2.25]);
});

test('f64x2_pmax', function () {
  expect(simdOp('f64x2.pmax', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([2.0, 0.5]);
});

test('f64x2_pmin', function () {
  expect(simdOp('f64x2.pmin', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([1.5, -2.25]);
});

test('f64x2_promote_low_f32x4', function () {
  expect(simdOp('f64x2.promote_low_f32x4', [v128(Shape::F32x4, [1.5, -0.25, 7.0, 8.0])], Shape::F64x2))->toBe([1.5, -0.25]);
});

test('f64x2_relaxed_madd', function () {
  expect(simdOp('f64x2.relaxed_madd', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5]), v128(Shape::F64x2, [10.0, 20.0])], Shape::F64x2))->toBe([13.0, 18.875]);
});

test('f64x2_relaxed_max', function () {
  expect(simdOp('f64x2.relaxed_max', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([2.0, 0.5]);
});

test('f64x2_relaxed_min', function () {
  expect(simdOp('f64x2.relaxed_min', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([1.5, -2.25]);
});

test('f64x2_relaxed_nmadd', function () {
  expect(simdOp('f64x2.relaxed_nmadd', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5]), v128(Shape::F64x2, [10.0, 20.0])], Shape::F64x2))->toBe([7.0, 21.125]);
});

test('f64x2_replace_lane', function () {
  expect(simdOp('f64x2.replace_lane 1', [v128(Shape::F64x2, [1.5, -2.25]), new F64(42.0)], Shape::F64x2))->toBe([1.5, 42.0]);
});

test('f64x2_splat', function () {
  expect(simdOp('f64x2.splat', [new F64(-1.5)], Shape::F64x2))->toBe([-1.5, -1.5]);
});

test('f64x2_sqrt', function () {
  expect(simdOp('f64x2.sqrt', [v128(Shape::F64x2, [6.25, 4.0])], Shape::F64x2))->toBe([2.5, 2.0]);
});

test('f64x2_sub', function () {
  expect(simdOp('f64x2.sub', [v128(Shape::F64x2, [1.5, -2.25]), v128(Shape::F64x2, [2.0, 0.5])], Shape::F64x2))->toBe([-0.5, -2.75]);
});

test('f64x2_trunc', function () {
  expect(simdOp('f64x2.trunc', [v128(Shape::F64x2, [1.5, -1.5])], Shape::F64x2))->toBe([1.0, -1.0]);
});
