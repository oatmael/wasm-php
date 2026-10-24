<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Type\ExternRef;
use Oatmael\WasmPhp\Type\FuncRef;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\NullRef;

test('ref_func', function () {
  $module = wat2module(<<<WAT
    (module
      (func \$seven (result i32) (i32.const 7))
      (elem declare func \$seven)
      (func (export "ref") (result funcref) (ref.func \$seven))
      (func (export "call") (result i32) (call_ref \$t (ref.func \$seven)))
      (type \$t (func (result i32)))
    )
    WAT);

  $ref = $module->execute('ref', [])[0];
  expect($ref)->toBeInstanceOf(FuncRef::class);
  expect($ref)->toBe($module->execute('ref', [])[0]);
  expect($module->execute('call', [])[0]->getValue())->toBe(7);
});

test('ref_is_null', function () {
  expect(wasmOp('ref.is_null', [new ExternRef(1)], 'i32', '')->getValue())->toBe(0);

  $module = wat2module(<<<WAT
    (module
      (func (export "f") (param externref) (result i32) (ref.is_null (local.get 0)))
    )
    WAT);
  expect($module->execute('f', [new NullRef()])[0]->getValue())->toBe(1);
  expect($module->execute('f', [new ExternRef(null)])[0]->getValue())->toBe(0);
});

test('ref_null', function () {
  $module = wat2module(<<<WAT
    (module
      (func (export "func") (result funcref) (ref.null func))
      (func (export "extern") (result externref) (ref.null extern))
    )
    WAT);

  expect($module->execute('func', [])[0])->toBeInstanceOf(NullRef::class);
  expect($module->execute('extern', [])[0])->toBeInstanceOf(NullRef::class);
});

test('ref_as_non_null', function () {
  $module = wat2module(<<<WAT
    (module
      (func (export "f") (param externref) (result (ref extern)) (ref.as_non_null (local.get 0)))
    )
    WAT);

  $ref = new ExternRef(1);
  expect($module->execute('f', [$ref])[0])->toBe($ref);
  expect(fn () => $module->execute('f', [new NullRef()]))->toThrow(TrapException::class, 'null reference');
});

test('ref_eq', function () {
  $module = wat2module(<<<WAT
    (module
      (type \$s (struct))
      (func (export "same") (result i32)
        (local \$s (ref \$s))
        (local.set \$s (struct.new \$s))
        (ref.eq (local.get \$s) (local.get \$s))
      )
      (func (export "different") (result i32)
        (ref.eq (struct.new \$s) (struct.new \$s))
      )
      (func (export "i31") (param i32 i32) (result i32)
        (ref.eq (ref.i31 (local.get 0)) (ref.i31 (local.get 1)))
      )
      (func (export "null") (result i32)
        (ref.eq (ref.null none) (ref.null \$s))
      )
    )
    WAT);

  expect($module->execute('same', [])[0]->getValue())->toBe(1);
  expect($module->execute('different', [])[0]->getValue())->toBe(0);
  expect($module->execute('i31', [new I32(5), new I32(5)])[0]->getValue())->toBe(1);
  expect($module->execute('i31', [new I32(5), new I32(6)])[0]->getValue())->toBe(0);
  expect($module->execute('null', [])[0]->getValue())->toBe(1);
});
