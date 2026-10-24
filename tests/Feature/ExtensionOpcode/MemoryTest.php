<?php

namespace Tests\Feature\ExtensionOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;

test('memory_copy', function (Module $module) {
  $module->execute('copy', [new I32(2), new I32(0), new I32(5)]);
  expect($module->getMemory()->read(0, 8))->toBe("hehello\0");

  // Out of bounds copies trap before anything is written
  expect(fn () => $module->execute('copy', [new I32(0), new I32(65535), new I32(2)]))->toThrow(TrapException::class, 'out of bounds memory access');
  expect($module->getMemory()->read(0, 2))->toBe('he');
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (memory 1)
      (data (i32.const 0) "hello")
      (func (export "copy") (param i32 i32 i32)
        (memory.copy (local.get 0) (local.get 1) (local.get 2))
      )
    )
    WAT),
]);

test('memory_fill', function (Module $module) {
  $module->execute('fill', [new I32(1), new I32(0x61), new I32(3)]);
  expect($module->getMemory()->read(0, 5))->toBe("\0aaa\0");

  expect(fn () => $module->execute('fill', [new I32(65535), new I32(0), new I32(2)]))->toThrow(TrapException::class, 'out of bounds memory access');
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "fill") (param i32 i32 i32)
        (memory.fill (local.get 0) (local.get 1) (local.get 2))
      )
    )
    WAT),
]);

test('memory_init', function (Module $module) {
  $module->execute('init', [new I32(4), new I32(1), new I32(3)]);
  expect($module->getMemory()->read(4, 3))->toBe('ell');

  expect(fn () => $module->execute('init', [new I32(0), new I32(4), new I32(2)]))->toThrow(TrapException::class, 'out of bounds memory access');
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (memory 1)
      (data \$passive "hello")
      (func (export "init") (param i32 i32 i32)
        (memory.init \$passive (local.get 0) (local.get 1) (local.get 2))
      )
    )
    WAT),
]);
