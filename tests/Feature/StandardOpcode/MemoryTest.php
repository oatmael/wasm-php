<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;

test('memory_grow', function (Module $module) {
    $ret = $module->execute('memory_grow', [new I32(1)]);
    expect($ret[0])->toBeInstanceOf(I32::class);
    expect($ret[0]->getValue())->toBe(1);
    expect($module->getMemory()->pages())->toBe(2);

    $ret = $module->execute('memory_grow', [new I32(1)]);
    expect($ret[0])->toBeInstanceOf(I32::class);
    expect($ret[0]->getValue())->toBe(2);
    expect($module->getMemory()->pages())->toBe(3);
})->with([
    'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "memory_grow") (param i32) (result i32)
        (memory.grow (local.get 0))
      )
    )
    WAT)
]);

test('memory_size', function (Module $module) {
    $ret = $module->execute('memory_size', []);
    expect($ret[0])->toBeInstanceOf(I32::class);
    expect($ret[0]->getValue())->toBe(1);

    $module->execute('memory_grow', [new I32(1)]);

    $ret = $module->execute('memory_size', []);
    expect($ret[0])->toBeInstanceOf(I32::class);
    expect($ret[0]->getValue())->toBe(2);
})->with([
    'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "memory_size") (result i32)
        (memory.size)
      ) 
      (func (export "memory_grow") (param i32) (result i32)
        (memory.grow (local.get 0))
      )
    )
    WAT)
]);

test('addresses multiple memories', function () {
  $module = wat2module(<<<WAT
    (module
      (memory \$a 1)
      (memory \$b 1)
      (func (export "f") (result i32)
        (i32.store \$b (i32.const 0) (i32.const 5))
        (i32.add (i32.load \$a (i32.const 0)) (i32.load \$b (i32.const 0)))
      )
    )
    WAT);

  expect($module->execute('f', [])[0]->getValue())->toBe(5);
  expect($module->getMemory(1)->read(0, 1))->toBe("\x05");
});

test('addresses memory64 with i64s', function () {
  $module = wat2module(<<<WAT
    (module
      (memory i64 1)
      (func (export "f") (param i64) (result i64)
        (i64.store (local.get 0) (i64.const 9))
        (drop (memory.grow (i64.const 1)))
        (i64.add (i64.load (local.get 0)) (memory.size))
      )
    )
    WAT);

  expect($module->execute('f', [new \Oatmael\WasmPhp\Type\I64(8)])[0]->getValue())->toBe(11);
  expect(fn () => $module->execute('f', [new \Oatmael\WasmPhp\Type\I64(-1)]))->toThrow(\Exception::class, 'out of bounds memory access');
});
