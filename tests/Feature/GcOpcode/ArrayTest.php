<?php

namespace Tests\Feature\GcOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\ArrayRef;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\NullRef;

// Arrays of mutable i16s, with functions to create them and read their elements back
function arrayModule(string $funcs = ''): Module
{
  return wat2module(<<<WAT
    (module
      (type \$shorts (array (mut i16)))
      (type \$funcs (array funcref))
      (data \$bytes "\\01\\00\\ff\\ff\\03\\00")
      (elem \$refs func \$first \$second)
      (func \$first)
      (func \$second)
      (func (export "get") (param (ref null \$shorts) i32) (result i32) (array.get \$shorts (local.get 0) (local.get 1)))
      (func (export "get_s") (param (ref null \$shorts) i32) (result i32) (array.get_s \$shorts (local.get 0) (local.get 1)))
      (func (export "get_u") (param (ref null \$shorts) i32) (result i32) (array.get_u \$shorts (local.get 0) (local.get 1)))
      (func (export "len") (param (ref null array)) (result i32) (array.len (local.get 0)))
      (func (export "new") (param i32 i32) (result (ref \$shorts)) (array.new \$shorts (local.get 0) (local.get 1)))
      $funcs
    )
    WAT);
}

// Reads every element of an array of i16s, sign extended
function shorts(Module $module, ArrayRef $array): array
{
  return array_map(fn (int $i) => $module->execute('get_s', [$array, new I32($i)])[0]->getValue(), array_keys($array->elements));
}

test('array_new', function () {
  $module = arrayModule();
  $array = $module->execute('new', [new I32(-2), new I32(3)])[0];

  expect($array)->toBeInstanceOf(ArrayRef::class);
  expect(shorts($module, $array))->toBe([-2, -2, -2]);
});

test('array_new_default', function () {
  $module = arrayModule('(func (export "new_default") (param i32) (result (ref $shorts)) (array.new_default $shorts (local.get 0)))');
  expect(shorts($module, $module->execute('new_default', [new I32(2)])[0]))->toBe([0, 0]);
});

test('array_new_fixed', function () {
  $module = arrayModule('(func (export "new_fixed") (result (ref $shorts)) (array.new_fixed $shorts 3 (i32.const 1) (i32.const 2) (i32.const 0x18000)))');
  expect(shorts($module, $module->execute('new_fixed', [])[0]))->toBe([1, 2, -32768]);
});

test('array_new_data', function () {
  $module = arrayModule('(func (export "new_data") (param i32 i32) (result (ref $shorts)) (array.new_data $shorts $bytes (local.get 0) (local.get 1)))');
  expect(shorts($module, $module->execute('new_data', [new I32(0), new I32(3)])[0]))->toBe([1, -1, 3]);
  expect(shorts($module, $module->execute('new_data', [new I32(1), new I32(2)])[0]))->toBe([-256, 0x3ff]);
  expect(fn () => $module->execute('new_data', [new I32(2), new I32(3)]))->toThrow(TrapException::class, 'out of bounds memory access');
});

test('array_new_elem', function () {
  $module = arrayModule(<<<WAT
    (func (export "new_elem") (param i32 i32) (result (ref \$funcs)) (array.new_elem \$funcs \$refs (local.get 0) (local.get 1)))
    WAT);

  $array = $module->execute('new_elem', [new I32(1), new I32(1)])[0];
  expect($array->elements)->toHaveCount(1);
  expect(fn () => $module->execute('new_elem', [new I32(1), new I32(2)]))->toThrow(TrapException::class, 'out of bounds table access');
});

test('array_get', function () {
  $module = arrayModule();
  $array = $module->execute('new', [new I32(5), new I32(2)])[0];

  expect($module->execute('get', [$array, new I32(1)])[0]->getValue())->toBe(5);
  expect(fn () => $module->execute('get', [$array, new I32(2)]))->toThrow(TrapException::class, 'out of bounds array access');
  expect(fn () => $module->execute('get', [new NullRef(), new I32(0)]))->toThrow(TrapException::class, 'null array reference');
});

test('array_get_s', function () {
  $module = arrayModule();
  expect($module->execute('get_s', [$module->execute('new', [new I32(0xffff), new I32(1)])[0], new I32(0)])[0]->getValue())->toBe(-1);
});

test('array_get_u', function () {
  $module = arrayModule();
  expect($module->execute('get_u', [$module->execute('new', [new I32(-1), new I32(1)])[0], new I32(0)])[0]->getValue())->toBe(0xffff);
});

test('array_set', function () {
  $module = arrayModule('(func (export "set") (param (ref null $shorts) i32 i32) (array.set $shorts (local.get 0) (local.get 1) (local.get 2)))');
  $array = $module->execute('new', [new I32(0), new I32(2)])[0];

  $module->execute('set', [$array, new I32(1), new I32(-7)]);
  expect(shorts($module, $array))->toBe([0, -7]);
  expect(fn () => $module->execute('set', [$array, new I32(2), new I32(0)]))->toThrow(TrapException::class, 'out of bounds array access');
});

test('array_len', function () {
  $module = arrayModule();
  expect($module->execute('len', [$module->execute('new', [new I32(0), new I32(4)])[0]])[0]->getValue())->toBe(4);
  expect(fn () => $module->execute('len', [new NullRef()]))->toThrow(TrapException::class, 'null array reference');
});

test('array_fill', function () {
  $module = arrayModule('(func (export "fill") (param (ref null $shorts) i32 i32 i32) (array.fill $shorts (local.get 0) (local.get 1) (local.get 2) (local.get 3)))');
  $array = $module->execute('new', [new I32(0), new I32(4)])[0];

  $module->execute('fill', [$array, new I32(1), new I32(9), new I32(2)]);
  expect(shorts($module, $array))->toBe([0, 9, 9, 0]);
  expect(fn () => $module->execute('fill', [$array, new I32(3), new I32(9), new I32(2)]))->toThrow(TrapException::class, 'out of bounds array access');
});

test('array_copy', function () {
  $module = arrayModule(<<<WAT
    (func (export "copy") (param (ref null \$shorts) i32 (ref null \$shorts) i32 i32)
      (array.copy \$shorts \$shorts (local.get 0) (local.get 1) (local.get 2) (local.get 3) (local.get 4))
    )
    (func (export "data") (result (ref \$shorts)) (array.new_data \$shorts \$bytes (i32.const 0) (i32.const 3)))
    WAT);
  $array = $module->execute('data', [])[0];

  // Overlapping copies behave as if the source was copied first
  $module->execute('copy', [$array, new I32(1), $array, new I32(0), new I32(2)]);
  expect(shorts($module, $array))->toBe([1, 1, -1]);
  expect(fn () => $module->execute('copy', [$array, new I32(2), $array, new I32(0), new I32(2)]))->toThrow(TrapException::class, 'out of bounds array access');
});

test('array_init_data', function () {
  $module = arrayModule('(func (export "init") (param (ref null $shorts) i32 i32 i32) (array.init_data $shorts $bytes (local.get 0) (local.get 1) (local.get 2) (local.get 3)))');
  $array = $module->execute('new', [new I32(0), new I32(3)])[0];

  $module->execute('init', [$array, new I32(1), new I32(2), new I32(2)]);
  expect(shorts($module, $array))->toBe([0, -1, 3]);
  expect(fn () => $module->execute('init', [$array, new I32(0), new I32(4), new I32(2)]))->toThrow(TrapException::class, 'out of bounds memory access');
});

test('array_init_elem', function () {
  $module = arrayModule(<<<WAT
    (func (export "new_funcs") (result (ref \$funcs)) (array.new_default \$funcs (i32.const 2)))
    (func (export "init") (param (ref null \$funcs) i32 i32 i32) (array.init_elem \$funcs \$refs (local.get 0) (local.get 1) (local.get 2) (local.get 3)))
    WAT);
  $array = $module->execute('new_funcs', [])[0];

  $module->execute('init', [$array, new I32(1), new I32(0), new I32(1)]);
  expect($array->elements[0])->toBeInstanceOf(NullRef::class);
  expect($array->elements[1])->toBeInstanceOf(\Oatmael\WasmPhp\Type\FuncRef::class);
  expect(fn () => $module->execute('init', [$array, new I32(0), new I32(1), new I32(2)]))->toThrow(TrapException::class, 'out of bounds table access');
});
