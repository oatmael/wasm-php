<?php

namespace Tests\Feature\GcOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I31Ref;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\NullRef;

function i31Module(): Module
{
  return wat2module(<<<WAT
    (module
      (func (export "new") (param i32) (result i31ref) (ref.i31 (local.get 0)))
      (func (export "get_s") (param i31ref) (result i32) (i31.get_s (local.get 0)))
      (func (export "get_u") (param i31ref) (result i32) (i31.get_u (local.get 0)))
    )
    WAT);
}

test('ref_i31', function () {
  $module = i31Module();
  $ref = $module->execute('new', [new I32(0x7fffffff)])[0];

  expect($ref)->toBeInstanceOf(I31Ref::class);
  // The top bit is dropped
  expect($module->execute('new', [new I32(-1)])[0]->value)->toBe(0x7fffffff);
});

test('i31_get_s', function () {
  $module = i31Module();
  expect($module->execute('get_s', [$module->execute('new', [new I32(0x40000000)])[0]])[0]->getValue())->toBe(-0x40000000);
  expect($module->execute('get_s', [$module->execute('new', [new I32(5)])[0]])[0]->getValue())->toBe(5);
  expect(fn () => $module->execute('get_s', [new NullRef()]))->toThrow(TrapException::class, 'null i31 reference');
});

test('i31_get_u', function () {
  $module = i31Module();
  expect($module->execute('get_u', [$module->execute('new', [new I32(-1)])[0]])[0]->getValue())->toBe(0x7fffffff);
  expect(fn () => $module->execute('get_u', [new NullRef()]))->toThrow(TrapException::class, 'null i31 reference');
});
