<?php

namespace Tests\Feature\SimdOpcode;

use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\Shape;

test('i16x8_abs', function () {
  expect(simdOp('i16x8.abs', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], Shape::I16x8))->toBe([0, 1, 1, 32767, -32768, 1000, 1000, 300]);
});

test('i16x8_add', function () {
  expect(simdOp('i16x8.add', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([1, 2, 0, -32768, -32767, 2000, 0, 0]);
});

test('i16x8_add_sat_s', function () {
  expect(simdOp('i16x8.add_sat_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([1, 2, 0, 32767, -32767, 2000, 0, 0]);
});

test('i16x8_add_sat_u', function () {
  expect(simdOp('i16x8.add_sat_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([1, 2, -1, -32768, -32767, 2000, -1, -1]);
});

test('i16x8_all_true', function () {
  expect(wasmOp('i16x8.all_true', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], 'i32')->getValue())->toBe(0);
  expect(wasmOp('i16x8.all_true', [v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], 'i32')->getValue())->toBe(1);
});

test('i16x8_avgr_u', function () {
  expect(simdOp('i16x8.avgr_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8, false))->toBe([1, 1, 32768, 16384, 16385, 1000, 32768, 32768]);
});

test('i16x8_bitmask', function () {
  expect(wasmOp('i16x8.bitmask', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], 'i32')->getValue())->toBe(84);
});

test('i16x8_eq', function () {
  expect(simdOp('i16x8.eq', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([0, -1, 0, 0, 0, -1, 0, 0]);
});

test('i16x8_extadd_pairwise_i8x16_s', function () {
  expect(simdOp('i16x8.extadd_pairwise_i8x16_s', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9])], Shape::I16x8))->toBe([1, 126, -28, -50, 5, 9, 13, 17]);
});

test('i16x8_extadd_pairwise_i8x16_u', function () {
  expect(simdOp('i16x8.extadd_pairwise_i8x16_u', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9])], Shape::I16x8))->toBe([1, 382, 228, 206, 5, 9, 13, 17]);
});

test('i16x8_extend_high_i8x16_s', function () {
  expect(simdOp('i16x8.extend_high_i8x16_s', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9])], Shape::I16x8))->toBe([2, 3, 4, 5, 6, 7, 8, 9]);
});

test('i16x8_extend_high_i8x16_u', function () {
  expect(simdOp('i16x8.extend_high_i8x16_u', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9])], Shape::I16x8, false))->toBe([2, 3, 4, 5, 6, 7, 8, 9]);
});

test('i16x8_extend_low_i8x16_s', function () {
  expect(simdOp('i16x8.extend_low_i8x16_s', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9])], Shape::I16x8))->toBe([0, 1, -1, 127, -128, 100, -100, 50]);
});

test('i16x8_extend_low_i8x16_u', function () {
  expect(simdOp('i16x8.extend_low_i8x16_u', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9])], Shape::I16x8, false))->toBe([0, 1, 255, 127, 128, 100, 156, 50]);
});

test('i16x8_extmul_high_i8x16_s', function () {
  expect(simdOp('i16x8.extmul_high_i8x16_s', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9]), v128(Shape::I8x16, [1, 1, 1, 1, 1, 100, 100, -50, 2, 2, 2, -2, 2, 2, 2, 2])], Shape::I16x8))->toBe([4, 6, 8, -10, 12, 14, 16, 18]);
});

test('i16x8_extmul_high_i8x16_u', function () {
  expect(simdOp('i16x8.extmul_high_i8x16_u', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9]), v128(Shape::I8x16, [1, 1, 1, 1, 1, 100, 100, -50, 2, 2, 2, -2, 2, 2, 2, 2])], Shape::I16x8))->toBe([4, 6, 8, 1270, 12, 14, 16, 18]);
});

test('i16x8_extmul_low_i8x16_s', function () {
  expect(simdOp('i16x8.extmul_low_i8x16_s', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9]), v128(Shape::I8x16, [1, 1, 1, 1, 1, 100, 100, -50, 2, 2, 2, -2, 2, 2, 2, 2])], Shape::I16x8))->toBe([0, 1, -1, 127, -128, 10000, -10000, -2500]);
});

test('i16x8_extmul_low_i8x16_u', function () {
  expect(simdOp('i16x8.extmul_low_i8x16_u', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9]), v128(Shape::I8x16, [1, 1, 1, 1, 1, 100, 100, -50, 2, 2, 2, -2, 2, 2, 2, 2])], Shape::I16x8))->toBe([0, 1, 255, 127, 128, 10000, 15600, 10300]);
});

test('i16x8_extract_lane_s', function () {
  expect(wasmOp('i16x8.extract_lane_s 2', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], 'i32')->getValue())->toBe(-1);
});

test('i16x8_extract_lane_u', function () {
  expect(wasmOp('i16x8.extract_lane_u 2', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], 'i32')->getValue())->toBe(65535);
});

test('i16x8_ge_s', function () {
  expect(simdOp('i16x8.ge_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([0, -1, 0, -1, 0, -1, 0, -1]);
});

test('i16x8_ge_u', function () {
  expect(simdOp('i16x8.ge_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([0, -1, -1, -1, -1, -1, -1, 0]);
});

test('i16x8_gt_s', function () {
  expect(simdOp('i16x8.gt_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([0, 0, 0, -1, 0, 0, 0, -1]);
});

test('i16x8_gt_u', function () {
  expect(simdOp('i16x8.gt_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([0, 0, -1, -1, -1, 0, -1, 0]);
});

test('i16x8_le_s', function () {
  expect(simdOp('i16x8.le_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([-1, -1, -1, 0, -1, -1, -1, 0]);
});

test('i16x8_le_u', function () {
  expect(simdOp('i16x8.le_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([-1, -1, 0, 0, 0, -1, 0, -1]);
});

test('i16x8_lt_s', function () {
  expect(simdOp('i16x8.lt_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([-1, 0, -1, 0, -1, 0, -1, 0]);
});

test('i16x8_lt_u', function () {
  expect(simdOp('i16x8.lt_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([-1, 0, 0, 0, 0, 0, 0, -1]);
});

test('i16x8_max_s', function () {
  expect(simdOp('i16x8.max_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([1, 1, 1, 32767, 1, 1000, 1000, 300]);
});

test('i16x8_max_u', function () {
  expect(simdOp('i16x8.max_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([1, 1, -1, 32767, -32768, 1000, -1000, -300]);
});

test('i16x8_min_s', function () {
  expect(simdOp('i16x8.min_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([0, 1, -1, 1, -32768, 1000, -1000, -300]);
});

test('i16x8_min_u', function () {
  expect(simdOp('i16x8.min_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([0, 1, 1, 1, 1, 1000, 1000, 300]);
});

test('i16x8_mul', function () {
  expect(simdOp('i16x8.mul', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([0, 1, -1, 32767, -32768, 16960, -16960, -24464]);
});

test('i16x8_narrow_i32x4_s', function () {
  expect(simdOp('i16x8.narrow_i32x4_s', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I16x8))->toBe([1, -1, 32767, -32768, 1, 1, 1, 1]);
});

test('i16x8_narrow_i32x4_u', function () {
  expect(simdOp('i16x8.narrow_i32x4_u', [v128(Shape::I32x4, [1, -1, 2147483647, -2147483648]), v128(Shape::I32x4, [1, 1, 1, 1])], Shape::I16x8))->toBe([1, 0, -1, 0, 1, 1, 1, 1]);
});

test('i16x8_ne', function () {
  expect(simdOp('i16x8.ne', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([-1, 0, -1, -1, -1, 0, -1, -1]);
});

test('i16x8_neg', function () {
  expect(simdOp('i16x8.neg', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300])], Shape::I16x8))->toBe([0, -1, 1, -32767, -32768, -1000, 1000, -300]);
});

test('i16x8_q15mulr_sat_s', function () {
  // Rounding, saturating Q15 multiplication
  expect(simdOp('i16x8.q15mulr_sat_s', [v128(Shape::I16x8, [16384, -32768, 100, -100, 32767, 0, 1, 2]), v128(Shape::I16x8, [16384, -32768, 200, 200, 32767, 5, 1, 2])], Shape::I16x8))->toBe([8192, 32767, 1, -1, 32766, 0, 0, 0]);
});

test('i16x8_relaxed_dot_i8x16_i7x16_s', function () {
  expect(simdOp('i16x8.relaxed_dot_i8x16_i7x16_s', [v128(Shape::I8x16, [0, 1, -1, 127, -128, 100, -100, 50, 2, 3, 4, 5, 6, 7, 8, 9]), v128(Shape::I8x16, [1, 1, 1, 1, 1, 100, 100, 78, 2, 2, 2, 126, 2, 2, 2, 2])], Shape::I16x8))->toBe([1, 126, 9872, -6100, 10, 638, 26, 34]);
});

test('i16x8_relaxed_laneselect', function () {
  // Takes lanes from the first operand where the mask is set
  expect(simdOp('i16x8.relaxed_laneselect', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300]), v128(Shape::I16x8, [0, -1, 0, -1, 0, -1, 0, -1])], Shape::I16x8))->toBe([1, 1, 1, 32767, 1, 1000, 1000, 300]);
});

test('i16x8_relaxed_q15mulr_s', function () {
  // Rounding, saturating Q15 multiplication
  expect(simdOp('i16x8.relaxed_q15mulr_s', [v128(Shape::I16x8, [16384, -32768, 100, -100, 32767, 0, 1, 2]), v128(Shape::I16x8, [16384, -32768, 200, 200, 32767, 5, 1, 2])], Shape::I16x8))->toBe([8192, 32767, 1, -1, 32766, 0, 0, 0]);
});

test('i16x8_replace_lane', function () {
  expect(simdOp('i16x8.replace_lane 1', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), new I32(42)], Shape::I16x8))->toBe([0, 42, -1, 32767, -32768, 1000, -1000, 300]);
});

test('i16x8_shl', function () {
  // Shifting by 17 shifts by 1
  expect(simdOp('i16x8.shl', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), new I32(17)], Shape::I16x8))->toBe([0, 2, -2, -2, 0, 2000, -2000, 600]);
});

test('i16x8_shr_s', function () {
  // Shifting by 17 shifts by 1
  expect(simdOp('i16x8.shr_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), new I32(17)], Shape::I16x8))->toBe([0, 0, -1, 16383, -16384, 500, -500, 150]);
});

test('i16x8_shr_u', function () {
  // Shifting by 17 shifts by 1
  expect(simdOp('i16x8.shr_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), new I32(17)], Shape::I16x8))->toBe([0, 0, 32767, 16383, 16384, 500, 32268, 150]);
});

test('i16x8_splat', function () {
  expect(simdOp('i16x8.splat', [new I32(-2)], Shape::I16x8))->toBe([-2, -2, -2, -2, -2, -2, -2, -2]);
});

test('i16x8_sub', function () {
  expect(simdOp('i16x8.sub', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([-1, 0, -2, 32766, 32767, 0, -2000, 600]);
});

test('i16x8_sub_sat_s', function () {
  expect(simdOp('i16x8.sub_sat_s', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([-1, 0, -2, 32766, -32768, 0, -2000, 600]);
});

test('i16x8_sub_sat_u', function () {
  expect(simdOp('i16x8.sub_sat_u', [v128(Shape::I16x8, [0, 1, -1, 32767, -32768, 1000, -1000, 300]), v128(Shape::I16x8, [1, 1, 1, 1, 1, 1000, 1000, -300])], Shape::I16x8))->toBe([0, 0, -2, 32766, 32767, 0, -2000, 0]);
});
