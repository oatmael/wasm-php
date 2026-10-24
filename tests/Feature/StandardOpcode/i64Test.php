<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Exception\BadIntegerCastException;
use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;

// Executes a single instruction over its arguments (ints are passed as i64s) and returns the result's value
function runI64(string $instruction, array $args, string $result = 'i64'): int|float
{
  $args = array_map(fn ($arg) => is_int($arg) ? new I64($arg) : $arg, $args);
  $params = implode(' ', array_map(fn ($arg) => match ($arg::class) {
    I32::class => 'i32',
    I64::class => 'i64',
    F32::class => 'f32',
    F64::class => 'f64',
  }, $args));
  $locals = implode(' ', array_map(fn (int $i) => "(local.get $i)", array_keys($args)));

  $module = wat2module(<<<WAT
    (module
      (func (export "f") (param $params) (result $result)
        ($instruction $locals)
      )
    )
    WAT);

  return $module->execute('f', $args)[0]->getValue();
}

// Executes $wat as the body of a function with one page of memory, and returns the memory
function runI64Memory(string $wat, string $result = ''): array
{
  $module = wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "f") (result $result)
        $wat
      )
    )
    WAT);

  return [$module->execute('f', []), $module->getMemory()];
}

test('i64_add', function (array $args, int $expected) {
  expect(runI64('i64.add', $args))->toBe($expected);
})->with([
  [[1, 2], 3],
  [[-1, -1], -2],
  'overflow'  => [[PHP_INT_MAX, 1], PHP_INT_MIN],
  'underflow' => [[PHP_INT_MIN, -1], PHP_INT_MAX],
]);

test('i64_and', function () {
  expect(runI64('i64.and', [0b1100, 0b1010]))->toBe(0b1000);
  expect(runI64('i64.and', [-1, PHP_INT_MIN]))->toBe(PHP_INT_MIN);
});

test('i64_clz', function (int $value, int $expected) {
  expect(runI64('i64.clz', [$value]))->toBe($expected);
})->with([
  [0, 64],
  [1, 63],
  [-1, 0],
  [PHP_INT_MAX, 1],
]);

test('i64_const', function (Module $module) {
  $ret = $module->execute('i64_const', []);
  expect($ret[0]->getValue())->toBe(PHP_INT_MIN);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i64_const") (result i64)
        (i64.const -9223372036854775808)
      )
    )
    WAT)
]);

test('i64_ctz', function (int $value, int $expected) {
  expect(runI64('i64.ctz', [$value]))->toBe($expected);
})->with([
  [0, 64],
  [8, 3],
  [-1, 0],
  [PHP_INT_MIN, 63],
]);

test('i64_div_s', function () {
  expect(runI64('i64.div_s', [7, 2]))->toBe(3);
  expect(runI64('i64.div_s', [-7, 2]))->toBe(-3);
  expect(fn () => runI64('i64.div_s', [1, 0]))->toThrow(TrapException::class, 'integer divide by zero');
  expect(fn () => runI64('i64.div_s', [PHP_INT_MIN, -1]))->toThrow(TrapException::class, 'integer overflow');
});

test('i64_div_u', function () {
  expect(runI64('i64.div_u', [7, 2]))->toBe(3);
  expect(runI64('i64.div_u', [-1, 2]))->toBe(PHP_INT_MAX);
  expect(runI64('i64.div_u', [-1, -1]))->toBe(1);
  expect(runI64('i64.div_u', [-2, -1]))->toBe(0);
  expect(fn () => runI64('i64.div_u', [1, 0]))->toThrow(TrapException::class, 'integer divide by zero');
});

test('i64_eq', function () {
  expect(runI64('i64.eq', [1, 1], 'i32'))->toBe(1);
  expect(runI64('i64.eq', [1, 2], 'i32'))->toBe(0);
});

test('i64_eqz', function () {
  expect(runI64('i64.eqz', [0], 'i32'))->toBe(1);
  expect(runI64('i64.eqz', [PHP_INT_MIN], 'i32'))->toBe(0);
});

test('i64_extend16_s', function () {
  expect(runI64('i64.extend16_s', [0x8000]))->toBe(-32768);
  expect(runI64('i64.extend16_s', [0x17FFF]))->toBe(32767);
});

test('i64_extend32_s', function () {
  expect(runI64('i64.extend32_s', [0x80000000]))->toBe(-2147483648);
  expect(runI64('i64.extend32_s', [0x17FFFFFFF]))->toBe(2147483647);
});

test('i64_extend8_s', function () {
  expect(runI64('i64.extend8_s', [0x80]))->toBe(-128);
  expect(runI64('i64.extend8_s', [0x17F]))->toBe(127);
  expect(runI64('i64.extend8_s', [-1]))->toBe(-1);
});

test('i64_extend_i32_s', function () {
  expect(runI64('i64.extend_i32_s', [new I32(-1)]))->toBe(-1);
  expect(runI64('i64.extend_i32_s', [new I32(42)]))->toBe(42);
});

test('i64_extend_i32_u', function () {
  expect(runI64('i64.extend_i32_u', [new I32(-1)]))->toBe(0xFFFFFFFF);
  expect(runI64('i64.extend_i32_u', [new I32(42)]))->toBe(42);
});

test('i64_ge_s', function () {
  expect(runI64('i64.ge_s', [-1, 1], 'i32'))->toBe(0);
  expect(runI64('i64.ge_s', [1, 1], 'i32'))->toBe(1);
});

test('i64_ge_u', function () {
  expect(runI64('i64.ge_u', [-1, 1], 'i32'))->toBe(1);
  expect(runI64('i64.ge_u', [PHP_INT_MAX, PHP_INT_MIN], 'i32'))->toBe(0);
  expect(runI64('i64.ge_u', [1, 1], 'i32'))->toBe(1);
});

test('i64_gt_s', function () {
  expect(runI64('i64.gt_s', [-1, 1], 'i32'))->toBe(0);
  expect(runI64('i64.gt_s', [1, -1], 'i32'))->toBe(1);
});

test('i64_gt_u', function () {
  expect(runI64('i64.gt_u', [-1, 1], 'i32'))->toBe(1);
  expect(runI64('i64.gt_u', [1, -1], 'i32'))->toBe(0);
});

test('i64_le_s', function () {
  expect(runI64('i64.le_s', [-1, 1], 'i32'))->toBe(1);
  expect(runI64('i64.le_s', [1, 1], 'i32'))->toBe(1);
  expect(runI64('i64.le_s', [1, -1], 'i32'))->toBe(0);
});

test('i64_le_u', function () {
  expect(runI64('i64.le_u', [-1, 1], 'i32'))->toBe(0);
  expect(runI64('i64.le_u', [1, 1], 'i32'))->toBe(1);
  expect(runI64('i64.le_u', [1, -1], 'i32'))->toBe(1);
});

test('i64_load', function () {
  [$ret] = runI64Memory('(i64.store (i32.const 8) (i64.const -2)) (i64.load (i32.const 8))', 'i64');
  expect($ret[0]->getValue())->toBe(-2);

  expect(fn () => runI64Memory('(i64.load (i32.const 65535))', 'i64'))->toThrow(\Exception::class, 'out of bounds memory access');
});

test('i64_load16_s', function () {
  [$ret] = runI64Memory('(i64.store (i32.const 0) (i64.const 0x8001)) (i64.load16_s (i32.const 0))', 'i64');
  expect($ret[0]->getValue())->toBe(-32767);
});

test('i64_load16_u', function () {
  [$ret] = runI64Memory('(i64.store (i32.const 0) (i64.const 0x8001)) (i64.load16_u (i32.const 0))', 'i64');
  expect($ret[0]->getValue())->toBe(0x8001);
});

test('i64_load32_s', function () {
  [$ret] = runI64Memory('(i64.store (i32.const 0) (i64.const 0x80000001)) (i64.load32_s (i32.const 0))', 'i64');
  expect($ret[0]->getValue())->toBe(-2147483647);
});

test('i64_load32_u', function () {
  [$ret] = runI64Memory('(i64.store (i32.const 0) (i64.const 0x80000001)) (i64.load32_u (i32.const 0))', 'i64');
  expect($ret[0]->getValue())->toBe(0x80000001);
});

test('i64_load8_s', function () {
  [$ret] = runI64Memory('(i64.store (i32.const 0) (i64.const 0x80)) (i64.load8_s (i32.const 0))', 'i64');
  expect($ret[0]->getValue())->toBe(-128);
});

test('i64_load8_u', function () {
  [$ret] = runI64Memory('(i64.store (i32.const 0) (i64.const 0x80)) (i64.load8_u offset=0 (i32.const 0))', 'i64');
  expect($ret[0]->getValue())->toBe(0x80);
});

test('i64_lt_s', function () {
  expect(runI64('i64.lt_s', [-1, 1], 'i32'))->toBe(1);
  expect(runI64('i64.lt_s', [1, 1], 'i32'))->toBe(0);
});

test('i64_lt_u', function () {
  expect(runI64('i64.lt_u', [-1, 1], 'i32'))->toBe(0);
  expect(runI64('i64.lt_u', [1, -1], 'i32'))->toBe(1);
  expect(runI64('i64.lt_u', [PHP_INT_MAX, PHP_INT_MIN], 'i32'))->toBe(1);
});

test('i64_mul', function (array $args, int $expected) {
  expect(runI64('i64.mul', $args))->toBe($expected);
})->with([
  [[6, 7], 42],
  [[-6, 7], -42],
  'overflow'          => [[PHP_INT_MAX, 2], -2],
  'wraps to zero'     => [[0x100000000, 0x100000000], 0],
  'large'             => [[3037000500, 3037000500], -9223372036709301616],
  'min times minus 1' => [[PHP_INT_MIN, -1], PHP_INT_MIN],
]);

test('i64_ne', function () {
  expect(runI64('i64.ne', [1, 1], 'i32'))->toBe(0);
  expect(runI64('i64.ne', [1, 2], 'i32'))->toBe(1);
});

test('i64_or', function () {
  expect(runI64('i64.or', [0b1100, 0b1010]))->toBe(0b1110);
  expect(runI64('i64.or', [1, PHP_INT_MIN]))->toBe(PHP_INT_MIN + 1);
});

test('i64_popcnt', function (int $value, int $expected) {
  expect(runI64('i64.popcnt', [$value]))->toBe($expected);
})->with([
  [0, 0],
  [0b1011, 3],
  [-1, 64],
  [PHP_INT_MIN, 1],
]);

test('i64_reinterpret_f64', function () {
  expect(runI64('i64.reinterpret_f64', [new F64(1.0)]))->toBe(0x3FF0000000000000);
  expect(runI64('i64.reinterpret_f64', [new F64(-0.0)]))->toBe(PHP_INT_MIN);
});

test('i64_rem_s', function () {
  expect(runI64('i64.rem_s', [-7, 2]))->toBe(-1);
  expect(runI64('i64.rem_s', [7, -2]))->toBe(1);
  expect(runI64('i64.rem_s', [PHP_INT_MIN, -1]))->toBe(0);
  expect(fn () => runI64('i64.rem_s', [1, 0]))->toThrow(TrapException::class, 'integer divide by zero');
});

test('i64_rem_u', function () {
  expect(runI64('i64.rem_u', [7, 3]))->toBe(1);
  // 2^64 - 1 = 18446744073709551615
  expect(runI64('i64.rem_u', [-1, 10]))->toBe(5);
  expect(fn () => runI64('i64.rem_u', [1, 0]))->toThrow(TrapException::class, 'integer divide by zero');
});

test('i64_rotl', function () {
  expect(runI64('i64.rotl', [PHP_INT_MIN, 1]))->toBe(1);
  expect(runI64('i64.rotl', [1, 65]))->toBe(2);
  expect(runI64('i64.rotl', [0x0123456789ABCDEF, 4]))->toBe(0x123456789ABCDEF0);
  expect(runI64('i64.rotl', [-8, 0]))->toBe(-8);
});

test('i64_rotr', function () {
  expect(runI64('i64.rotr', [1, 1]))->toBe(PHP_INT_MIN);
  expect(runI64('i64.rotr', [0x123456789ABCDEF0, 4]))->toBe(0x0123456789ABCDEF);
  expect(runI64('i64.rotr', [-8, 64]))->toBe(-8);
});

test('i64_shl', function () {
  expect(runI64('i64.shl', [3, 1]))->toBe(6);
  expect(runI64('i64.shl', [1, 63]))->toBe(PHP_INT_MIN);
  expect(runI64('i64.shl', [1, 64]))->toBe(1);
});

test('i64_shr_s', function () {
  expect(runI64('i64.shr_s', [-8, 1]))->toBe(-4);
  expect(runI64('i64.shr_s', [PHP_INT_MIN, 63]))->toBe(-1);
});

test('i64_shr_u', function () {
  expect(runI64('i64.shr_u', [16, 2]))->toBe(4);
  expect(runI64('i64.shr_u', [-1, 1]))->toBe(PHP_INT_MAX);
  expect(runI64('i64.shr_u', [PHP_INT_MIN, 63]))->toBe(1);
  expect(runI64('i64.shr_u', [-8, 0]))->toBe(-8);
});

test('i64_store', function () {
  [, $memory] = runI64Memory('(i64.store (i32.const 0) (i64.const 0x0102030405060708))');
  expect(array_values(unpack("C*", $memory->read(0, 9))))->toBe([8, 7, 6, 5, 4, 3, 2, 1, 0]);
});

test('i64_store16', function () {
  [, $memory] = runI64Memory('(i64.store16 (i32.const 0) (i64.const 0x0102030405060708))');
  expect(array_values(unpack("C*", $memory->read(0, 3))))->toBe([8, 7, 0]);
});

test('i64_store32', function () {
  [, $memory] = runI64Memory('(i64.store32 (i32.const 0) (i64.const 0x0102030405060708))');
  expect(array_values(unpack("C*", $memory->read(0, 5))))->toBe([8, 7, 6, 5, 0]);
});

test('i64_store8', function () {
  [, $memory] = runI64Memory('(i64.store8 offset=1 (i32.const 0) (i64.const -1))');
  expect(array_values(unpack("C*", $memory->read(0, 3))))->toBe([0, 0xFF, 0]);
});

test('i64_sub', function (array $args, int $expected) {
  expect(runI64('i64.sub', $args))->toBe($expected);
})->with([
  [[3, 5], -2],
  'underflow' => [[PHP_INT_MIN, 1], PHP_INT_MAX],
  'overflow'  => [[PHP_INT_MAX, -1], PHP_INT_MIN],
]);

test('i64_trunc_f32_s', function () {
  expect(runI64('i64.trunc_f32_s', [new F32(1.5)]))->toBe(1);
  expect(runI64('i64.trunc_f32_s', [new F32(-2.5)]))->toBe(-2);
  expect(runI64('i64.trunc_f32_s', [new F32(-9223372036854775808.0)]))->toBe(PHP_INT_MIN);
  expect(fn () => runI64('i64.trunc_f32_s', [new F32(9223372036854775808.0)]))->toThrow(BadIntegerCastException::class);
  expect(fn () => runI64('i64.trunc_f32_s', [new F32(NAN)]))->toThrow(BadIntegerCastException::class);
});

test('i64_trunc_f32_u', function () {
  expect(runI64('i64.trunc_f32_u', [new F32(1.5)]))->toBe(1);
  // 1e19 rounds to 9999999980506447872 as an f32, which wraps when stored as a signed int
  expect(runI64('i64.trunc_f32_u', [new F32(1.0e19)]))->toBe(-8446744093203103744);
  expect(fn () => runI64('i64.trunc_f32_u', [new F32(18446744073709551616.0)]))->toThrow(BadIntegerCastException::class);
  expect(fn () => runI64('i64.trunc_f32_u', [new F32(-1.0)]))->toThrow(BadIntegerCastException::class);
});

test('i64_trunc_f64_s', function () {
  expect(runI64('i64.trunc_f64_s', [new F64(1.9)]))->toBe(1);
  expect(runI64('i64.trunc_f64_s', [new F64(-1.9)]))->toBe(-1);
  expect(runI64('i64.trunc_f64_s', [new F64(9.2e18)]))->toBe(9200000000000000000);
  expect(fn () => runI64('i64.trunc_f64_s', [new F64(9223372036854775808.0)]))->toThrow(BadIntegerCastException::class);
  expect(fn () => runI64('i64.trunc_f64_s', [new F64(INF)]))->toThrow(BadIntegerCastException::class);
});

test('i64_trunc_f64_u', function () {
  expect(runI64('i64.trunc_f64_u', [new F64(1.9)]))->toBe(1);
  expect(runI64('i64.trunc_f64_u', [new F64(-0.5)]))->toBe(0);
  expect(runI64('i64.trunc_f64_u', [new F64(9223372036854775808.0)]))->toBe(PHP_INT_MIN);
  // The largest f64 below 2^64 is 18446744073709549568, i.e. -2048 as a signed int
  expect(runI64('i64.trunc_f64_u', [new F64(18446744073709549568.0)]))->toBe(-2048);
  expect(fn () => runI64('i64.trunc_f64_u', [new F64(18446744073709551616.0)]))->toThrow(BadIntegerCastException::class);
  expect(fn () => runI64('i64.trunc_f64_u', [new F64(-1.0)]))->toThrow(BadIntegerCastException::class);
});

test('i64_xor', function () {
  expect(runI64('i64.xor', [0b1100, 0b1010]))->toBe(0b0110);
  expect(runI64('i64.xor', [-1, PHP_INT_MAX]))->toBe(PHP_INT_MIN);
});
