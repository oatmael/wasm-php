<?php

namespace Tests\Feature\GcOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\ExternRef;
use Oatmael\WasmPhp\Type\HostRef;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\NullRef;

// A struct type with a subtype, and a function that returns a reference of each kind:
// 0 - null, 1 - i31, 2 - $base struct, 3 - $derived struct, 4 - array
function castModule(string $funcs): Module
{
  return wat2module(<<<WAT
    (module
      (type \$base (sub (struct (field i32))))
      (type \$derived (sub \$base (struct (field i32) (field i64))))
      (type \$array (array i8))
      (func \$value (param i32) (result anyref)
        (if (i32.eq (local.get 0) (i32.const 1)) (then (return (ref.i31 (i32.const 7)))))
        (if (i32.eq (local.get 0) (i32.const 2)) (then (return (struct.new_default \$base))))
        (if (i32.eq (local.get 0) (i32.const 3)) (then (return (struct.new_default \$derived))))
        (if (i32.eq (local.get 0) (i32.const 4)) (then (return (array.new_default \$array (i32.const 1)))))
        (ref.null any)
      )
      $funcs
    )
    WAT);
}

// The result of calling "f" with each kind of reference
function castResults(Module $module): array
{
  return array_map(fn (int $kind) => $module->execute('f', [new I32($kind)])[0]->getValue(), range(0, 4));
}

test('ref_test', function () {
  $module = castModule('(func (export "f") (param i32) (result i32) (ref.test (ref $base) (call $value (local.get 0))))');
  // Subtypes match their supertypes, but nulls don't match non-nullable types
  expect(castResults($module))->toBe([0, 0, 1, 1, 0]);

  $module = castModule('(func (export "f") (param i32) (result i32) (ref.test (ref eq) (call $value (local.get 0))))');
  expect(castResults($module))->toBe([0, 1, 1, 1, 1]);
});

test('ref_test_null', function () {
  $module = castModule('(func (export "f") (param i32) (result i32) (ref.test (ref null $derived) (call $value (local.get 0))))');
  expect(castResults($module))->toBe([1, 0, 0, 1, 0]);
});

test('ref_cast', function () {
  $module = castModule('(func (export "f") (param i32) (result i32) (ref.is_null (ref.cast (ref struct) (call $value (local.get 0)))))');
  expect($module->execute('f', [new I32(3)])[0]->getValue())->toBe(0);
  expect(fn () => $module->execute('f', [new I32(1)]))->toThrow(TrapException::class, 'cast failure');
  expect(fn () => $module->execute('f', [new I32(0)]))->toThrow(TrapException::class, 'cast failure');
});

test('ref_cast_null', function () {
  $module = castModule('(func (export "f") (param i32) (result i32) (ref.is_null (ref.cast (ref null i31) (call $value (local.get 0)))))');
  expect($module->execute('f', [new I32(0)])[0]->getValue())->toBe(1);
  expect($module->execute('f', [new I32(1)])[0]->getValue())->toBe(0);
  expect(fn () => $module->execute('f', [new I32(2)]))->toThrow(TrapException::class, 'cast failure');
});

test('br_on_cast', function () {
  $module = castModule(<<<WAT
    (func (export "f") (param i32) (result i32)
      (block \$is_array (result (ref \$array))
        (br_on_cast \$is_array anyref (ref \$array) (call \$value (local.get 0)))
        (return (i32.const -1))
      )
      (array.len)
    )
    WAT);

  expect(castResults($module))->toBe([-1, -1, -1, -1, 1]);
});

test('br_on_cast_fail', function () {
  $module = castModule(<<<WAT
    (func (export "f") (param i32) (result i32)
      (block \$not_i31 (result anyref)
        (return (i31.get_u (br_on_cast_fail \$not_i31 anyref (ref i31) (call \$value (local.get 0)))))
      )
      (drop)
      (i32.const -1)
    )
    WAT);

  expect(castResults($module))->toBe([-1, 7, -1, -1, -1]);
});

test('any_convert_extern', function () {
  $module = wat2module(<<<WAT
    (module
      (func (export "f") (param externref) (result anyref) (any.convert_extern (local.get 0)))
    )
    WAT);

  // Host values become HostRefs in the any hierarchy
  $ret = $module->execute('f', [new ExternRef('host')])[0];
  expect($ret)->toBeInstanceOf(HostRef::class);
  expect($ret->value)->toBe('host');
  expect($module->execute('f', [new NullRef()])[0])->toBeInstanceOf(NullRef::class);
});

test('extern_convert_any', function () {
  $module = wat2module(<<<WAT
    (module
      (func (export "round_trip") (param i32) (result i32)
        (i31.get_s (ref.cast (ref i31) (any.convert_extern (extern.convert_any (ref.i31 (local.get 0))))))
      )
      (func (export "host") (param externref) (result externref) (extern.convert_any (any.convert_extern (local.get 0))))
    )
    WAT);

  // Internal references survive a round trip through externref
  expect($module->execute('round_trip', [new I32(-5)])[0]->getValue())->toBe(-5);
  expect($module->execute('host', [new ExternRef(42)])[0]->value)->toBe(42);
});
