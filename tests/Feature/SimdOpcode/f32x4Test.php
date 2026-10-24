<?php

namespace Tests\Feature\SimdOpcode;

use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Util\Shape;

test('f32x4_abs', function () {
  expect(simdOp('f32x4.abs', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0])], Shape::F32x4))->toBe([1.5, 2.25, 0.5, 100.0]);
});

test('f32x4_add', function () {
  expect(simdOp('f32x4.add', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([3.5, -1.75, 0.0, 103.0]);
});

test('f32x4_ceil', function () {
  expect(simdOp('f32x4.ceil', [v128(Shape::F32x4, [1.5, -1.5, 2.5, -0.4])], Shape::F32x4))->toBe([2.0, -1.0, 3.0, 0.0]);
});

test('f32x4_convert_i32x4_s', function () {
  // 16777217 rounds to the nearest f32
  expect(simdOp('f32x4.convert_i32x4_s', [v128(Shape::I32x4, [1, -1, 16777217, -2147483648])], Shape::F32x4))->toBe([1.0, -1.0, 16777216.0, -2147483648.0]);
});

test('f32x4_convert_i32x4_u', function () {
  expect(simdOp('f32x4.convert_i32x4_u', [v128(Shape::I32x4, [1, -1, 3, 0])], Shape::F32x4))->toBe([1.0, 4294967296.0, 3.0, 0.0]);
});

test('f32x4_demote_f64x2_zero', function () {
  expect(simdOp('f32x4.demote_f64x2_zero', [v128(Shape::F64x2, [1.5, 0.1])], Shape::F32x4))->toBe([1.5, 0.10000000149011612, 0.0, 0.0]);
});

test('f32x4_div', function () {
  expect(simdOp('f32x4.div', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([0.75, -4.5, -1.0, 33.33333206176758]);
});

test('f32x4_eq', function () {
  expect(simdOp('f32x4.eq', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [1.5, 0.5, 0.5, 200.0])], Shape::I32x4))->toBe([-1, 0, -1, 0]);
});

test('f32x4_extract_lane', function () {
  expect(wasmOp('f32x4.extract_lane 1', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0])], 'f32')->getValue())->toBe(-2.25);
});

test('f32x4_floor', function () {
  expect(simdOp('f32x4.floor', [v128(Shape::F32x4, [1.5, -1.5, 2.5, -0.4])], Shape::F32x4))->toBe([1.0, -2.0, 2.0, -1.0]);
});

test('f32x4_ge', function () {
  expect(simdOp('f32x4.ge', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [1.5, 0.5, 0.5, 200.0])], Shape::I32x4))->toBe([-1, 0, -1, 0]);
});

test('f32x4_gt', function () {
  expect(simdOp('f32x4.gt', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [1.5, 0.5, 0.5, 200.0])], Shape::I32x4))->toBe([0, 0, 0, 0]);
});

test('f32x4_le', function () {
  expect(simdOp('f32x4.le', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [1.5, 0.5, 0.5, 200.0])], Shape::I32x4))->toBe([-1, -1, -1, -1]);
});

test('f32x4_lt', function () {
  expect(simdOp('f32x4.lt', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [1.5, 0.5, 0.5, 200.0])], Shape::I32x4))->toBe([0, -1, 0, -1]);
});

test('f32x4_max', function () {
  expect(simdOp('f32x4.max', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([2.0, 0.5, 0.5, 100.0]);
});

test('f32x4_min', function () {
  expect(simdOp('f32x4.min', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([1.5, -2.25, -0.5, 3.0]);
});

test('f32x4_mul', function () {
  expect(simdOp('f32x4.mul', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([3.0, -1.125, -0.25, 300.0]);
});

test('f32x4_ne', function () {
  expect(simdOp('f32x4.ne', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [1.5, 0.5, 0.5, 200.0])], Shape::I32x4))->toBe([0, -1, 0, -1]);
});

test('f32x4_nearest', function () {
  // Ties round to even
  expect(simdOp('f32x4.nearest', [v128(Shape::F32x4, [1.5, -1.5, 2.5, -0.4])], Shape::F32x4))->toBe([2.0, -2.0, 2.0, 0.0]);
});

test('f32x4_neg', function () {
  expect(simdOp('f32x4.neg', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0])], Shape::F32x4))->toBe([-1.5, 2.25, -0.5, -100.0]);
});

test('f32x4_pmax', function () {
  expect(simdOp('f32x4.pmax', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([2.0, 0.5, 0.5, 100.0]);
});

test('f32x4_pmin', function () {
  expect(simdOp('f32x4.pmin', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([1.5, -2.25, -0.5, 3.0]);
});

test('f32x4_relaxed_madd', function () {
  expect(simdOp('f32x4.relaxed_madd', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0]), v128(Shape::F32x4, [10.0, 20.0, 30.0, 40.0])], Shape::F32x4))->toBe([13.0, 18.875, 29.75, 340.0]);
});

test('f32x4_relaxed_max', function () {
  expect(simdOp('f32x4.relaxed_max', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([2.0, 0.5, 0.5, 100.0]);
});

test('f32x4_relaxed_min', function () {
  expect(simdOp('f32x4.relaxed_min', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([1.5, -2.25, -0.5, 3.0]);
});

test('f32x4_relaxed_nmadd', function () {
  expect(simdOp('f32x4.relaxed_nmadd', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0]), v128(Shape::F32x4, [10.0, 20.0, 30.0, 40.0])], Shape::F32x4))->toBe([7.0, 21.125, 30.25, -260.0]);
});

test('f32x4_replace_lane', function () {
  expect(simdOp('f32x4.replace_lane 1', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), new F32(42.0)], Shape::F32x4))->toBe([1.5, 42.0, 0.5, 100.0]);
});

test('f32x4_splat', function () {
  expect(simdOp('f32x4.splat', [new F32(-1.5)], Shape::F32x4))->toBe([-1.5, -1.5, -1.5, -1.5]);
});

test('f32x4_sqrt', function () {
  expect(simdOp('f32x4.sqrt', [v128(Shape::F32x4, [6.25, 4.0, 0.25, 9.0])], Shape::F32x4))->toBe([2.5, 2.0, 0.5, 3.0]);
});

test('f32x4_sub', function () {
  expect(simdOp('f32x4.sub', [v128(Shape::F32x4, [1.5, -2.25, 0.5, 100.0]), v128(Shape::F32x4, [2.0, 0.5, -0.5, 3.0])], Shape::F32x4))->toBe([-0.5, -2.75, 1.0, 97.0]);
});

test('f32x4_trunc', function () {
  expect(simdOp('f32x4.trunc', [v128(Shape::F32x4, [1.5, -1.5, 2.5, -0.4])], Shape::F32x4))->toBe([1.0, -1.0, 2.0, 0.0]);
});
