<?php

namespace Tests\Feature\SimdOpcode;

use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\Shape;

test('i32x4_abs', function () {
  expect(simdOp('i32x4.abs', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648])], Shape::I32x4))->toBe([1, 1, 2147483647, -2147483648]);
});

test('i32x4_add', function () {
  expect(simdOp('i32x4.add', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([2, 0, -2147483648, -2147483647]);
});

test('i32x4_all_true', function () {
  expect(wasmOp('i32x4.all_true', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648])], 'i32')->getValue())->toBe(1);
  expect(wasmOp('i32x4.all_true', [v128(Shape::I32x4, [1, 1, 1, 1])], 'i32')->getValue())->toBe(1);
});

test('i32x4_bitmask', function () {
  expect(wasmOp('i32x4.bitmask', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648])], 'i32')->getValue())->toBe(10);
});

test('i32x4_dot_i16x8_s', function () {
  expect(simdOp('i32x4.dot_i16x8_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I32x4))->toBe([1, 32766, 967232, -1090000]);
});

test('i32x4_eq', function () {
  expect(simdOp('i32x4.eq', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([-1, 0, 0, 0]);
});

test('i32x4_extadd_pairwise_i16x8_s', function () {
  expect(simdOp('i32x4.extadd_pairwise_i16x8_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], Shape::I32x4))->toBe([1, 32766, -31768, -700]);
});

test('i32x4_extadd_pairwise_i16x8_u', function () {
  expect(simdOp('i32x4.extadd_pairwise_i16x8_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], Shape::I32x4))->toBe([1, 98302, 33768, 64836]);
});

test('i32x4_extend_high_i16x8_s', function () {
  expect(simdOp('i32x4.extend_high_i16x8_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], Shape::I32x4))->toBe([-32768, 1000, -1000, 300]);
});

test('i32x4_extend_high_i16x8_u', function () {
  expect(simdOp('i32x4.extend_high_i16x8_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], Shape::I32x4, false))->toBe([32768, 1000, 64536, 300]);
});

test('i32x4_extend_low_i16x8_s', function () {
  expect(simdOp('i32x4.extend_low_i16x8_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], Shape::I32x4))->toBe([0, 1, -1, 32767]);
});

test('i32x4_extend_low_i16x8_u', function () {
  expect(simdOp('i32x4.extend_low_i16x8_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], Shape::I32x4, false))->toBe([0, 1, 65535, 32767]);
});

test('i32x4_extmul_high_i16x8_s', function () {
  expect(simdOp('i32x4.extmul_high_i16x8_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I32x4))->toBe([-32768, 1000000, -1000000, -90000]);
});

test('i32x4_extmul_high_i16x8_u', function () {
  expect(simdOp('i32x4.extmul_high_i16x8_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I32x4))->toBe([32768, 1000000, 64536000, 19570800]);
});

test('i32x4_extmul_low_i16x8_s', function () {
  expect(simdOp('i32x4.extmul_low_i16x8_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I32x4))->toBe([0, 1, -1, 32767]);
});

test('i32x4_extmul_low_i16x8_u', function () {
  expect(simdOp('i32x4.extmul_low_i16x8_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I32x4))->toBe([0, 1, 65535, 32767]);
});

test('i32x4_extract_lane', function () {
  expect(wasmOp('i32x4.extract_lane 1', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648])], 'i32')->getValue())->toBe(-1);
});

test('i32x4_ge_s', function () {
  expect(simdOp('i32x4.ge_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([-1, 0, -1, 0]);
});

test('i32x4_ge_u', function () {
  expect(simdOp('i32x4.ge_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([-1, -1, -1, -1]);
});

test('i32x4_gt_s', function () {
  expect(simdOp('i32x4.gt_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([0, 0, -1, 0]);
});

test('i32x4_gt_u', function () {
  expect(simdOp('i32x4.gt_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([0, -1, -1, -1]);
});

test('i32x4_le_s', function () {
  expect(simdOp('i32x4.le_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([-1, -1, 0, -1]);
});

test('i32x4_le_u', function () {
  expect(simdOp('i32x4.le_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([-1, 0, 0, 0]);
});

test('i32x4_lt_s', function () {
  expect(simdOp('i32x4.lt_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([0, -1, 0, -1]);
});

test('i32x4_lt_u', function () {
  expect(simdOp('i32x4.lt_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([0, 0, 0, 0]);
});

test('i32x4_max_s', function () {
  expect(simdOp('i32x4.max_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([1, 1, 2147483647, 1]);
});

test('i32x4_max_u', function () {
  expect(simdOp('i32x4.max_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([1, -1, 2147483647, -2147483648]);
});

test('i32x4_min_s', function () {
  expect(simdOp('i32x4.min_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([1, -1, 1, -2147483648]);
});

test('i32x4_min_u', function () {
  expect(simdOp('i32x4.min_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([1, 1, 1, 1]);
});

test('i32x4_mul', function () {
  expect(simdOp('i32x4.mul', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([1, -1, 2147483647, -2147483648]);
});

test('i32x4_ne', function () {
  expect(simdOp('i32x4.ne', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([0, -1, -1, -1]);
});

test('i32x4_neg', function () {
  expect(simdOp('i32x4.neg', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648])], Shape::I32x4))->toBe([-1, 1, -2147483647, -2147483648]);
});

test('i32x4_relaxed_dot_i8x16_i7x16_add_s', function () {
  expect(simdOp('i32x4.relaxed_dot_i8x16_i7x16_add_s', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9]), v128(Shape::I8x16, [1, 1, 1, 1, 1, 100, 100, 78, 2, 2, 2, 126, 2, 2, 2, 2]), v128(Shape::I32x4, [1, 2, 3, 4])], Shape::I32x4))->toBe([128, 3774, 651, 64]);
});

test('i32x4_relaxed_laneselect', function () {
  // Takes lanes from the first operand where the mask is set
  expect(simdOp('i32x4.relaxed_laneselect', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1]), v128(Shape::I32x4, [0, -1, 0, -1])], Shape::I32x4))->toBe([1, -1, 1, -2147483648]);
});

test('i32x4_relaxed_trunc_f32x4_s', function () {
  // Out of range values saturate, and NaN converts to 0
  expect(simdOp('i32x4.relaxed_trunc_f32x4_s', [v128(Shape::F32x4, [1.9, -1.9, 3000000000.0, NAN])], Shape::I32x4))->toBe([1, -1, 2147483647, 0]);
});

test('i32x4_relaxed_trunc_f32x4_u', function () {
  // Out of range values saturate
  expect(simdOp('i32x4.relaxed_trunc_f32x4_u', [v128(Shape::F32x4, [1.9, -1.9, 5000000000.0, 3000000000.0])], Shape::I32x4, false))->toBe([1, 0, 4294967295, 3000000000]);
});

test('i32x4_relaxed_trunc_f64x2_s_zero', function () {
  expect(simdOp('i32x4.relaxed_trunc_f64x2_s_zero', [v128(Shape::F64x2, [-1.9, -3000000000.0])], Shape::I32x4))->toBe([-1, -2147483648, 0, 0]);
});

test('i32x4_relaxed_trunc_f64x2_u_zero', function () {
  expect(simdOp('i32x4.relaxed_trunc_f64x2_u_zero', [v128(Shape::F64x2, [4294967295.5, -1.0])], Shape::I32x4, false))->toBe([4294967295, 0, 0, 0]);
});

test('i32x4_replace_lane', function () {
  expect(simdOp('i32x4.replace_lane 1', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), new I32(42)], Shape::I32x4))->toBe([1, 42, 2147483647, -2147483648]);
});

test('i32x4_shl', function () {
  // Shifting by 33 shifts by 1
  expect(simdOp('i32x4.shl', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), new I32(33)], Shape::I32x4))->toBe([2, -2, -2, 0]);
});

test('i32x4_shr_s', function () {
  // Shifting by 33 shifts by 1
  expect(simdOp('i32x4.shr_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), new I32(33)], Shape::I32x4))->toBe([0, -1, 1073741823, -1073741824]);
});

test('i32x4_shr_u', function () {
  // Shifting by 33 shifts by 1
  expect(simdOp('i32x4.shr_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), new I32(33)], Shape::I32x4))->toBe([0, 2147483647, 1073741823, 1073741824]);
});

test('i32x4_splat', function () {
  expect(simdOp('i32x4.splat', [new I32(-2)], Shape::I32x4))->toBe([-2, -2, -2, -2]);
});

test('i32x4_sub', function () {
  expect(simdOp('i32x4.sub', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I32x4))->toBe([0, -2, 2147483646, 2147483647]);
});

test('i32x4_trunc_sat_f32x4_s', function () {
  // Out of range values saturate, and NaN converts to 0
  expect(simdOp('i32x4.trunc_sat_f32x4_s', [v128(Shape::F32x4, [1.9, -1.9, 3000000000.0, NAN])], Shape::I32x4))->toBe([1, -1, 2147483647, 0]);
});

test('i32x4_trunc_sat_f32x4_u', function () {
  // Out of range values saturate
  expect(simdOp('i32x4.trunc_sat_f32x4_u', [v128(Shape::F32x4, [1.9, -1.9, 5000000000.0, 3000000000.0])], Shape::I32x4, false))->toBe([1, 0, 4294967295, 3000000000]);
});

test('i32x4_trunc_sat_f64x2_s_zero', function () {
  expect(simdOp('i32x4.trunc_sat_f64x2_s_zero', [v128(Shape::F64x2, [-1.9, -3000000000.0])], Shape::I32x4))->toBe([-1, -2147483648, 0, 0]);
});

test('i32x4_trunc_sat_f64x2_u_zero', function () {
  expect(simdOp('i32x4.trunc_sat_f64x2_u_zero', [v128(Shape::F64x2, [4294967295.5, -1.0])], Shape::I32x4, false))->toBe([4294967295, 0, 0, 0]);
});
