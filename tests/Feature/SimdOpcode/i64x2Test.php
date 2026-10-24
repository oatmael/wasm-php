<?php

namespace Tests\Feature\SimdOpcode;

use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Util\Shape;

test('i64x2_abs', function () {
  expect(simdOp('i64x2.abs', [v128(Shape::I64x2, [-1, 9223372036854775807])], Shape::I64x2))->toBe([1, 9223372036854775807]);
});

test('i64x2_add', function () {
  expect(simdOp('i64x2.add', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1])], Shape::I64x2))->toBe([0, PHP_INT_MIN]);
});

test('i64x2_all_true', function () {
  expect(wasmOp('i64x2.all_true', [v128(Shape::I64x2, [-1, 9223372036854775807])], 'i32')->getValue())->toBe(1);
  expect(wasmOp('i64x2.all_true', [v128(Shape::I64x2, [1, 1])], 'i32')->getValue())->toBe(1);
});

test('i64x2_bitmask', function () {
  expect(wasmOp('i64x2.bitmask', [v128(Shape::I64x2, [-1, 9223372036854775807])], 'i32')->getValue())->toBe(1);
});

test('i64x2_eq', function () {
  expect(simdOp('i64x2.eq', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1])], Shape::I64x2))->toBe([0, 0]);
});

test('i64x2_extend_high_i32x4_s', function () {
  expect(simdOp('i64x2.extend_high_i32x4_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648])], Shape::I64x2))->toBe([2147483647, -2147483648]);
});

test('i64x2_extend_high_i32x4_u', function () {
  expect(simdOp('i64x2.extend_high_i32x4_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648])], Shape::I64x2, false))->toBe([2147483647, 2147483648]);
});

test('i64x2_extend_low_i32x4_s', function () {
  expect(simdOp('i64x2.extend_low_i32x4_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648])], Shape::I64x2))->toBe([1, -1]);
});

test('i64x2_extend_low_i32x4_u', function () {
  expect(simdOp('i64x2.extend_low_i32x4_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648])], Shape::I64x2, false))->toBe([1, 4294967295]);
});

test('i64x2_extmul_high_i32x4_s', function () {
  expect(simdOp('i64x2.extmul_high_i32x4_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I64x2))->toBe([2147483647, -2147483648]);
});

test('i64x2_extmul_high_i32x4_u', function () {
  expect(simdOp('i64x2.extmul_high_i32x4_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I64x2))->toBe([2147483647, 2147483648]);
});

test('i64x2_extmul_low_i32x4_s', function () {
  expect(simdOp('i64x2.extmul_low_i32x4_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I64x2))->toBe([1, -1]);
});

test('i64x2_extmul_low_i32x4_u', function () {
  expect(simdOp('i64x2.extmul_low_i32x4_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I64x2))->toBe([1, 4294967295]);
});

test('i64x2_extract_lane', function () {
  expect(wasmOp('i64x2.extract_lane 1', [v128(Shape::I64x2, [-1, 9223372036854775807])], 'i64')->getValue())->toBe(9223372036854775807);
});

test('i64x2_ge_s', function () {
  expect(simdOp('i64x2.ge_s', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1])], Shape::I64x2))->toBe([0, -1]);
});

test('i64x2_gt_s', function () {
  expect(simdOp('i64x2.gt_s', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1])], Shape::I64x2))->toBe([0, -1]);
});

test('i64x2_le_s', function () {
  expect(simdOp('i64x2.le_s', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1])], Shape::I64x2))->toBe([-1, 0]);
});

test('i64x2_lt_s', function () {
  expect(simdOp('i64x2.lt_s', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1])], Shape::I64x2))->toBe([-1, 0]);
});

test('i64x2_mul', function () {
  expect(simdOp('i64x2.mul', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1])], Shape::I64x2))->toBe([-1, 9223372036854775807]);
});

test('i64x2_ne', function () {
  expect(simdOp('i64x2.ne', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1])], Shape::I64x2))->toBe([-1, -1]);
});

test('i64x2_neg', function () {
  expect(simdOp('i64x2.neg', [v128(Shape::I64x2, [-1, 9223372036854775807])], Shape::I64x2))->toBe([1, -9223372036854775807]);
});

test('i64x2_relaxed_laneselect', function () {
  // Takes lanes from the first operand where the mask is set
  expect(simdOp('i64x2.relaxed_laneselect', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1]), v128(Shape::I64x2, [0, -1])], Shape::I64x2))->toBe([1, 9223372036854775807]);
});

test('i64x2_replace_lane', function () {
  expect(simdOp('i64x2.replace_lane 1', [v128(Shape::I64x2, [-1, 9223372036854775807]), new I64(42)], Shape::I64x2))->toBe([-1, 42]);
});

test('i64x2_shl', function () {
  // Shifting by 65 shifts by 1
  expect(simdOp('i64x2.shl', [v128(Shape::I64x2, [-1, 9223372036854775807]), new I32(65)], Shape::I64x2))->toBe([-2, -2]);
});

test('i64x2_shr_s', function () {
  // Shifting by 65 shifts by 1
  expect(simdOp('i64x2.shr_s', [v128(Shape::I64x2, [-1, 9223372036854775807]), new I32(65)], Shape::I64x2))->toBe([-1, 4611686018427387903]);
});

test('i64x2_shr_u', function () {
  // Shifting by 65 shifts by 1
  expect(simdOp('i64x2.shr_u', [v128(Shape::I64x2, [-1, 9223372036854775807]), new I32(65)], Shape::I64x2))->toBe([9223372036854775807, 4611686018427387903]);
});

test('i64x2_splat', function () {
  expect(simdOp('i64x2.splat', [new I64(-2)], Shape::I64x2))->toBe([-2, -2]);
});

test('i64x2_sub', function () {
  expect(simdOp('i64x2.sub', [v128(Shape::I64x2, [-1, 9223372036854775807]), v128(Shape::I64x2, [1, 1])], Shape::I64x2))->toBe([-2, 9223372036854775806]);
});
