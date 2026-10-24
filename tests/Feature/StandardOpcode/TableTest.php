<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\ExternRef;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\NullRef;

test('table_get', function (Module $module) {
  expect($module->execute('get', [new I32(0)])[0])->toBeInstanceOf(NullRef::class);
  expect(fn () => $module->execute('get', [new I32(2)]))->toThrow(TrapException::class, 'out of bounds table access');
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (table 2 externref)
      (func (export "get") (param i32) (result externref) (table.get (local.get 0)))
    )
    WAT),
]);

test('table_set', function (Module $module) {
  $module->execute('set', [new I32(1), new ExternRef(42)]);
  expect($module->execute('get', [new I32(1)])[0]->value)->toBe(42);
  expect(fn () => $module->execute('set', [new I32(2), new NullRef()]))->toThrow(TrapException::class, 'out of bounds table access');
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (table 2 externref)
      (func (export "get") (param i32) (result externref) (table.get (local.get 0)))
      (func (export "set") (param i32 externref) (table.set (local.get 0) (local.get 1)))
    )
    WAT),
]);
