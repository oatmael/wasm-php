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
