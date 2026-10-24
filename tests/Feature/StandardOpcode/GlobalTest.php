<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;

test('global_get', function (Module $module) {
  $ret = $module->execute('global_get', []);
  expect($ret[0]->value)->toBe(42);
})
  ->with([
    'module' => fn() => wat2module(<<<WAT
    (module
      (global \$global (mut i32) (i32.const 42))
      (func (export "global_get") (result i32)
        (global.get \$global)
      )
    )
    WAT)
  ]);

test('global_set', function (Module $module) {
  $ret = $module->execute('global_set', [new I32(43)]);
  expect($ret[0]->value)->toBe(43);
})
  ->depends('global_get')
  ->with([
    'module' => fn() => wat2module(<<<WAT
    (module
      (global \$global (mut i32) (i32.const 42))
      (func (export "global_set") (param i32) (result i32)
        (global.set \$global (local.get 0))
        (global.get \$global)
      )
    )
    WAT)
  ]);

test('evaluates extended constant expressions', function () {
  $module = wat2module(<<<WAT
    (module
      (global \$base i32 (i32.const 10))
      (global \$derived i32 (i32.mul (i32.add (global.get \$base) (i32.const 2)) (i32.const 3)))
      (func (export "f") (result i32) (global.get \$derived))
    )
    WAT);

  expect($module->execute('f', [])[0]->getValue())->toBe(36);
});
