<?php

namespace Tests\Feature\GcOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\StructRef;

// A struct with an i32, a mutable packed i8 and a mutable f64, and functions to create and access it
function structModule(): Module
{
  return wat2module(<<<WAT
    (module
      (type \$point (struct (field i32) (field \$flag (mut i8)) (field \$weight (mut f64))))
      (func (export "new") (param i32 i32) (result (ref \$point))
        (struct.new \$point (local.get 0) (local.get 1) (f64.const 1.5))
      )
      (func (export "new_default") (result (ref \$point)) (struct.new_default \$point))
      (func (export "get") (param (ref null \$point)) (result i32) (struct.get \$point 0 (local.get 0)))
      (func (export "get_s") (param (ref null \$point)) (result i32) (struct.get_s \$point \$flag (local.get 0)))
      (func (export "get_u") (param (ref null \$point)) (result i32) (struct.get_u \$point \$flag (local.get 0)))
      (func (export "get_weight") (param (ref null \$point)) (result f64) (struct.get \$point \$weight (local.get 0)))
      (func (export "set") (param (ref null \$point) i32) (struct.set \$point \$flag (local.get 0) (local.get 1)))
    )
    WAT);
}

test('struct_new', function () {
  $module = structModule();
  $struct = $module->execute('new', [new I32(7), new I32(0x1ff)])[0];

  expect($struct)->toBeInstanceOf(StructRef::class);
  expect($module->execute('get', [$struct])[0]->getValue())->toBe(7);
  // Packed fields are wrapped to their width
  expect($module->execute('get_u', [$struct])[0]->getValue())->toBe(0xff);
  expect($module->execute('get_weight', [$struct])[0]->getValue())->toBe(1.5);
});

test('struct_new_default', function () {
  $module = structModule();
  $struct = $module->execute('new_default', [])[0];

  expect($module->execute('get', [$struct])[0]->getValue())->toBe(0);
  expect($module->execute('get_weight', [$struct])[0]->getValue())->toBe(0.0);
});

test('struct_get', function () {
  $module = structModule();
  expect($module->execute('get', [$module->execute('new', [new I32(-3), new I32(0)])[0]])[0]->getValue())->toBe(-3);
  expect(fn () => $module->execute('get', [new \Oatmael\WasmPhp\Type\NullRef()]))->toThrow(TrapException::class, 'null structure reference');
});

test('struct_get_s', function () {
  $module = structModule();
  expect($module->execute('get_s', [$module->execute('new', [new I32(0), new I32(0x80)])[0]])[0]->getValue())->toBe(-128);
});

test('struct_get_u', function () {
  $module = structModule();
  expect($module->execute('get_u', [$module->execute('new', [new I32(0), new I32(0x80)])[0]])[0]->getValue())->toBe(128);
});

test('struct_set', function () {
  $module = structModule();
  $struct = $module->execute('new_default', [])[0];

  $module->execute('set', [$struct, new I32(-1)]);
  expect($module->execute('get_s', [$struct])[0]->getValue())->toBe(-1);
  expect($module->execute('get_u', [$struct])[0]->getValue())->toBe(255);
  expect(fn () => $module->execute('set', [new \Oatmael\WasmPhp\Type\NullRef(), new I32(1)]))->toThrow(TrapException::class, 'null structure reference');
});
