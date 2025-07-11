<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;

test('local_get', function (Module $module) {
  $ret = $module->execute('local_get', [new I32(42)]);
  expect($ret[0]->value)->toBe(42);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "local_get") (param i32) (result i32)
        (local.get 0)
      )
    )
    WAT)
]);

test('local_set', function (Module $module) {
  $ret = $module->execute('local_set', []);
  expect($ret[0]->value)->toBe(42);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "local_set") (param) (result i32)
        (local \$local i32)
        (local.set \$local (i32.const 42))
        (local.get \$local)
      )
    )
    WAT)
]);

test('local_tee', function (Module $module) {
  $ret = $module-> execute('local_tee', [new I32(42)]);
  expect($ret[0]->value)->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "local_tee") (param i32) (result i32)
        (i32.const 0)
        (local.tee 0)
      )
    )
    WAT)
]);
