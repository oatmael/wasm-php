<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\Memory;

test('memory_grow', function (Module $module) {
    $ret = $module->execute('memory_grow', [new I32(1)]);
    expect($ret[0]->value)->toBe(1);
    expect($module->getMemory()->data)->toHaveCount(Memory::PAGE_SIZE * 2);

    $ret = $module->execute('memory_grow', [new I32(1)]);
    expect($ret[0]->value)->toBe(2);
    expect($module->getMemory()->data)->toHaveCount(Memory::PAGE_SIZE * 3);
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
    expect($ret[0]->value)->toBe(1);

    $module->execute('memory_grow', [new I32(1)]);

    $ret = $module->execute('memory_size', []);
    expect($ret[0]->value)->toBe(2);
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
