<?php

namespace Tests\Feature\SimdOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\V128;
use Oatmael\WasmPhp\Util\Shape;

// One page of memory, starting with the bytes 1 to 16, then 0x80 and 0xff
const SIMD_MEMORY = <<<'WAT'
  (memory 1)
  (data (i32.const 0) "\01\02\03\04\05\06\07\08\09\0a\0b\0c\0d\0e\0f\10\80\ff")
  WAT;

// Stores $vector at address 32 with $instruction, and returns the 16 bytes there
function simdStore(string $instruction, V128 $vector): string
{
  $module = wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "f") (param v128)
        ($instruction (i32.const 32) (local.get 0))
      )
    )
    WAT);

  $module->execute('f', [$vector]);
  return $module->getMemory()->read(32, 16);
}

function bytes(array $bytes): V128
{
  return v128(Shape::I8x16, $bytes);
}

test('v128_and', function () {
  expect(simdOp('v128.and', [bytes([0b1100, ...array_fill(0, 15, 0xff)]), bytes([0b1010, ...array_fill(0, 15, 0x0f)])], Shape::I8x16, false))
    ->toBe([0b1000, ...array_fill(0, 15, 0x0f)]);
});

test('v128_andnot', function () {
  expect(simdOp('v128.andnot', [bytes([0b1100, ...array_fill(0, 15, 0xff)]), bytes([0b1010, ...array_fill(0, 15, 0x0f)])], Shape::I8x16, false))
    ->toBe([0b0100, ...array_fill(0, 15, 0xf0)]);
});

test('v128_any_true', function () {
  expect(wasmOp('v128.any_true', [bytes([...array_fill(0, 16, 0)])], 'i32')->getValue())->toBe(0);
  expect(wasmOp('v128.any_true', [bytes([...array_fill(0, 15, 0), 1])], 'i32')->getValue())->toBe(1);
});

test('v128_bitselect', function () {
  $mask = v128(Shape::I32x4, [0xFFFF0000, 0, -1, 0x0F0F0F0F]);
  expect(simdOp('v128.bitselect', [v128(Shape::I32x4, [-1, -1, 0x12345678, -1]), v128(Shape::I32x4, [0, 0x87654321, 0, 0]), $mask], Shape::I32x4, false))
    ->toBe([0xFFFF0000, 0x87654321, 0x12345678, 0x0F0F0F0F]);
});

test('v128_const', function () {
  expect(simdOp('v128.const i32x4 1 -2 3 0x7fffffff', [], Shape::I32x4))->toBe([1, -2, 3, 0x7fffffff]);
  expect(simdOp('v128.const f64x2 1.5 -0.25', [], Shape::F64x2))->toBe([1.5, -0.25]);
});

test('v128_load', function () {
  expect(simdOp('v128.load', [new I32(0)], Shape::I8x16, false, SIMD_MEMORY))->toBe(range(1, 16));
  expect(simdOp('v128.load offset=2', [new I32(0)], Shape::I8x16, false, SIMD_MEMORY))->toBe([...range(3, 16), 0x80, 0xff]);
  expect(fn () => simdOp('v128.load', [new I32(65521)], Shape::I8x16, false, SIMD_MEMORY))->toThrow(TrapException::class, 'out of bounds memory access');
});

test('v128_load16_lane', function () {
  expect(simdOp('v128.load16_lane 1', [new I32(0), bytes([...array_fill(0, 16, 0)])], Shape::I16x8, true, SIMD_MEMORY))->toBe([0, 0x0201, 0, 0, 0, 0, 0, 0]);
});

test('v128_load16_splat', function () {
  expect(simdOp('v128.load16_splat', [new I32(0)], Shape::I16x8, true, SIMD_MEMORY))->toBe(array_fill(0, 8, 0x0201));
});

test('v128_load16x4_s', function () {
  expect(simdOp('v128.load16x4_s', [new I32(14)], Shape::I32x4, true, SIMD_MEMORY))->toBe([0x100f, -128, 0, 0]);
});

test('v128_load16x4_u', function () {
  expect(simdOp('v128.load16x4_u', [new I32(14)], Shape::I32x4, true, SIMD_MEMORY))->toBe([0x100f, 0xff80, 0, 0]);
});

test('v128_load32_lane', function () {
  expect(simdOp('v128.load32_lane 3', [new I32(0), bytes([...array_fill(0, 16, 0)])], Shape::I32x4, true, SIMD_MEMORY))->toBe([0, 0, 0, 0x04030201]);
});

test('v128_load32_splat', function () {
  expect(simdOp('v128.load32_splat', [new I32(0)], Shape::I32x4, true, SIMD_MEMORY))->toBe(array_fill(0, 4, 0x04030201));
});

test('v128_load32_zero', function () {
  expect(simdOp('v128.load32_zero', [new I32(0)], Shape::I32x4, true, SIMD_MEMORY))->toBe([0x04030201, 0, 0, 0]);
});

test('v128_load32x2_s', function () {
  expect(simdOp('v128.load32x2_s', [new I32(14)], Shape::I64x2, true, SIMD_MEMORY))->toBe([0xff80100f - 0x100000000, 0]);
});

test('v128_load32x2_u', function () {
  expect(simdOp('v128.load32x2_u', [new I32(14)], Shape::I64x2, true, SIMD_MEMORY))->toBe([0xff80100f, 0]);
});

test('v128_load64_lane', function () {
  expect(simdOp('v128.load64_lane 1', [new I32(0), bytes([...array_fill(0, 16, 0)])], Shape::I64x2, true, SIMD_MEMORY))->toBe([0, 0x0807060504030201]);
});

test('v128_load64_splat', function () {
  expect(simdOp('v128.load64_splat', [new I32(0)], Shape::I64x2, true, SIMD_MEMORY))->toBe([0x0807060504030201, 0x0807060504030201]);
});

test('v128_load64_zero', function () {
  expect(simdOp('v128.load64_zero', [new I32(0)], Shape::I64x2, true, SIMD_MEMORY))->toBe([0x0807060504030201, 0]);
});

test('v128_load8_lane', function () {
  expect(simdOp('v128.load8_lane 3', [new I32(16), bytes([...range(1, 16)])], Shape::I8x16, true, SIMD_MEMORY))->toBe([1, 2, 3, -128, ...range(5, 16)]);
});

test('v128_load8_splat', function () {
  expect(simdOp('v128.load8_splat', [new I32(16)], Shape::I8x16, true, SIMD_MEMORY))->toBe(array_fill(0, 16, -128));
});

test('v128_load8x8_s', function () {
  expect(simdOp('v128.load8x8_s', [new I32(14)], Shape::I16x8, true, SIMD_MEMORY))->toBe([15, 16, -128, -1, 0, 0, 0, 0]);
});

test('v128_load8x8_u', function () {
  expect(simdOp('v128.load8x8_u', [new I32(14)], Shape::I16x8, true, SIMD_MEMORY))->toBe([15, 16, 128, 255, 0, 0, 0, 0]);
});

test('v128_not', function () {
  expect(simdOp('v128.not', [v128(Shape::I32x4, [0, -1, 0x0F0F0F0F, 1])], Shape::I32x4))->toBe([-1, 0, -0x0F0F0F10, -2]);
});

test('v128_or', function () {
  expect(simdOp('v128.or', [bytes([0b1100, ...array_fill(0, 15, 0xf0)]), bytes([0b1010, ...array_fill(0, 15, 0x0f)])], Shape::I8x16, false))
    ->toBe([0b1110, ...array_fill(0, 15, 0xff)]);
});

test('v128_store', function () {
  expect(simdStore('v128.store', bytes([...range(1, 16)])))->toBe(pack('C*', ...range(1, 16)));
});

test('v128_store16_lane', function () {
  expect(simdStore('v128.store16_lane 1', bytes([...range(1, 16)])))->toBe("\x03\x04" . str_repeat("\0", 14));
});

test('v128_store32_lane', function () {
  expect(simdStore('v128.store32_lane 2', bytes([...range(1, 16)])))->toBe("\x09\x0a\x0b\x0c" . str_repeat("\0", 12));
});

test('v128_store64_lane', function () {
  expect(simdStore('v128.store64_lane 1', bytes([...range(1, 16)])))->toBe(pack('C*', ...range(9, 16)) . str_repeat("\0", 8));
});

test('v128_store8_lane', function () {
  expect(simdStore('v128.store8_lane 15', bytes([...range(1, 16)])))->toBe("\x10" . str_repeat("\0", 15));
});

test('v128_xor', function () {
  expect(simdOp('v128.xor', [bytes([0b1100, ...array_fill(0, 15, 0xff)]), bytes([0b1010, ...array_fill(0, 15, 0x0f)])], Shape::I8x16, false))
    ->toBe([0b0110, ...array_fill(0, 15, 0xf0)]);
});
