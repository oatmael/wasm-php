<?php

namespace Tests\Feature\ExtensionOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\ExternRef;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\NullRef;

// A table of externrefs with accessors, for testing the table instructions
function tableModule(string $funcs): Module
{
  return wat2module(<<<WAT
    (module
      (table \$t 3 10 externref)
      (func (export "get") (param i32) (result externref) (table.get \$t (local.get 0)))
      (func (export "set") (param i32 externref) (table.set \$t (local.get 0) (local.get 1)))
      $funcs
    )
    WAT);
}

test('table_copy', function () {
  $module = tableModule('(func (export "copy") (param i32 i32 i32) (table.copy (local.get 0) (local.get 1) (local.get 2)))');
  $module->execute('set', [new I32(0), new ExternRef('a')]);
  $module->execute('set', [new I32(1), new ExternRef('b')]);

  $module->execute('copy', [new I32(1), new I32(0), new I32(2)]);
  expect($module->execute('get', [new I32(1)])[0]->value)->toBe('a');
  expect($module->execute('get', [new I32(2)])[0]->value)->toBe('b');

  expect(fn () => $module->execute('copy', [new I32(2), new I32(0), new I32(2)]))->toThrow(TrapException::class, 'out of bounds table access');
});

test('table_fill', function () {
  $module = tableModule('(func (export "fill") (param i32 externref i32) (table.fill (local.get 0) (local.get 1) (local.get 2)))');

  $module->execute('fill', [new I32(1), new ExternRef('x'), new I32(2)]);
  expect($module->execute('get', [new I32(0)])[0])->toBeInstanceOf(NullRef::class);
  expect($module->execute('get', [new I32(2)])[0]->value)->toBe('x');

  expect(fn () => $module->execute('fill', [new I32(2), new NullRef(), new I32(2)]))->toThrow(TrapException::class, 'out of bounds table access');
});

test('table_grow', function () {
  $module = tableModule('(func (export "grow") (param externref i32) (result i32) (table.grow (local.get 0) (local.get 1)))');

  expect($module->execute('grow', [new ExternRef('x'), new I32(2)])[0]->getValue())->toBe(3);
  expect($module->execute('get', [new I32(4)])[0]->value)->toBe('x');

  // Growing past the maximum fails without trapping
  expect($module->execute('grow', [new NullRef(), new I32(6)])[0]->getValue())->toBe(-1);
});

test('table_init', function () {
  $module = wat2module(<<<WAT
    (module
      (table 3 funcref)
      (func \$one (result i32) (i32.const 1))
      (func \$two (result i32) (i32.const 2))
      (elem \$passive func \$one \$two)
      (func (export "init") (param i32 i32 i32) (table.init \$passive (local.get 0) (local.get 1) (local.get 2)))
      (func (export "call") (param i32) (result i32) (call_indirect (result i32) (local.get 0)))
    )
    WAT);

  $module->execute('init', [new I32(1), new I32(0), new I32(2)]);
  expect($module->execute('call', [new I32(1)])[0]->getValue())->toBe(1);
  expect($module->execute('call', [new I32(2)])[0]->getValue())->toBe(2);

  expect(fn () => $module->execute('init', [new I32(2), new I32(0), new I32(2)]))->toThrow(TrapException::class, 'out of bounds table access');
});

test('table_size', function () {
  $module = tableModule(<<<WAT
    (func (export "size") (result i32) (table.size \$t))
    (func (export "grow") (param i32) (result i32) (table.grow \$t (ref.null extern) (local.get 0)))
    WAT);

  expect($module->execute('size', [])[0]->getValue())->toBe(3);
  $module->execute('grow', [new I32(4)]);
  expect($module->execute('size', [])[0]->getValue())->toBe(7);
});
