<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;

test('i32_add', function (Module $module) {
  $ret = $module->execute('i32_add', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(85);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_add") (param i32) (param i32) (result i32)
        (i32.add (local.get 0) (local.get 1))
      )
    )
    WAT)
  ]);

test('i32_and', function (Module $module) {
  $ret = $module->execute('i32_and', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(42 & 43);

})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_and") (param i32) (param i32) (result i32)
        (i32.and (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_clz', function (Module $module) {
  $ret = $module->execute('i32_clz', [new I32(0b1010)]);
  expect($ret[0]->value)->toBe(28);

})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_clz") (param i32) (result i32)
        (i32.clz (local.get 0))
      )
    )
    WAT)
]);

test('i32_const', function (Module $module) {
  $ret = $module->execute('i32_const', []);
  expect($ret[0]->value)->toBe(42);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_const") (result i32)
        (i32.const 42)
      )
    )
    WAT)
]);

test('i32_ctz', function (Module $module) {
  $ret = $module->execute('i32_ctz', [new I32(0b1000)]);
  expect($ret[0]->value)->toBe(3);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_ctz") (param i32) (result i32)
        (i32.ctz (local.get 0))
      )
    )
    WAT)
]);

test('i32_div_s', function (Module $module) {
  $ret = $module->execute('i32_div_s', [new I32(6), new I32(42)]);
  expect($ret[0]->value)->toBe(7);

  $ret = $module->execute('i32_div_s', [new I32(-6), new I32(-42)]);
  expect($ret[0]->value)->toBe(7);

  $ret = $module->execute('i32_div_s', [new I32(6), new I32(-42)]);
  expect($ret[0]->value)->toBe(-7);

  $ret = $module->execute('i32_div_s', [new I32(-6), new I32(42)]);
  expect($ret[0]->value)->toBe(-7);

})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_div_s") (param i32) (param i32) (result i32)
        (i32.div_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_div_u', function (Module $module) {
  $ret = $module->execute('i32_div_u', [new I32(6), new I32(42)]);
  expect($ret[0]->value)->toBe(7);

  $ret = $module->execute('i32_div_u', [new I32(-6), new I32(-42)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_div_u', [new I32(6), new I32(-42)]);
  expect($ret[0]->value)->toBe(715827875);

  $ret = $module->execute('i32_div_u', [new I32(-6), new I32(42)]);
  expect($ret[0]->value)->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_div_u") (param i32) (param i32) (result i32)
        (i32.div_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_eq', function (Module $module) {
  $ret = $module->execute('i32_eq', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_eq', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(1);

})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_eq") (param i32) (param i32) (result i32)
        (i32.eq (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_eqz', function (Module $module) {
  $ret = $module->execute('i32_eqz', [new I32(0)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_eqz', [new I32(42)]);
  expect($ret[0]->value)->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_eqz") (param i32) (result i32)
        (i32.eqz (local.get 0))
      )
    )
    WAT)
]);

test('i32_extend16_s', function () {})->todo();

test('i32_extend8_s', function () {})->todo();

test('i32_ge_s', function (Module $module) {
  $ret = $module->execute('i32_ge_s', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_ge_s', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_ge_s', [new I32(-43), new I32(42)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_ge_s', [new I32(42), new I32(-43)]);
  expect($ret[0]->value)->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_ge_s") (param i32) (param i32) (result i32)
        (i32.ge_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_ge_u', function (Module $module) {
  $ret = $module->execute('i32_ge_u', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_ge_u', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_ge_u', [new I32(-43), new I32(42)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_ge_u', [new I32(42), new I32(-43)]);
  expect($ret[0]->value)->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_ge_u") (param i32) (param i32) (result i32)
        (i32.ge_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_gt_s', function (Module $module) {
  $ret = $module->execute('i32_gt_s', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_gt_s', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_gt_s', [new I32(-43), new I32(42)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_gt_s', [new I32(42), new I32(-43)]);
  expect($ret[0]->value)->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_gt_s") (param i32) (param i32) (result i32)
        (i32.gt_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_gt_u', function (Module $module) {
  $ret = $module->execute('i32_gt_u', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_gt_u', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_gt_u', [new I32(-43), new I32(42)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_gt_u', [new I32(42), new I32(-43)]);
  expect($ret[0]->value)->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_gt_u") (param i32) (param i32) (result i32)
        (i32.gt_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_le_s', function (Module $module) {
  $ret = $module->execute('i32_le_s', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_le_s', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_le_s', [new I32(-43), new I32(42)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_le_s', [new I32(42), new I32(-43)]);
  expect($ret[0]->value)->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_le_s") (param i32) (param i32) (result i32)
        (i32.le_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_le_u', function (Module $module) {
  $ret = $module->execute('i32_le_u', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_le_u', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_le_u', [new I32(-43), new I32(42)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_le_u', [new I32(42), new I32(-43)]);
  expect($ret[0]->value)->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_le_u") (param i32) (param i32) (result i32)
        (i32.le_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_load', function () {})->todo();

test('i32_load16_s', function () {})->todo();

test('i32_load16_u', function () {})->todo();

test('i32_load8_s', function () {})->todo();

test('i32_load8_u', function () {})->todo();

test('i32_lt_s', function (Module $module) {
  $ret = $module->execute('i32_lt_s', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_lt_s', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_lt_s', [new I32(-43), new I32(42)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_lt_s', [new I32(42), new I32(-43)]);
  expect($ret[0]->value)->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_lt_s") (param i32) (param i32) (result i32)
        (i32.lt_s (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_lt_u', function (Module $module) {
  $ret = $module->execute('i32_lt_u', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_lt_u', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('i32_lt_u', [new I32(-43), new I32(42)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_lt_u', [new I32(42), new I32(-43)]);
  expect($ret[0]->value)->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_lt_u") (param i32) (param i32) (result i32)
        (i32.lt_u (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_mul', function (Module $module) {
  $ret = $module->execute('i32_mul', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(1806);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_mul") (param i32) (param i32) (result i32)
        (i32.mul (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_ne', function (Module $module) {
  $ret = $module->execute('i32_ne', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('i32_ne', [new I32(42), new I32(42)]);
  expect($ret[0]->value)->toBe(0);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_ne") (param i32) (param i32) (result i32)
        (i32.ne (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_or', function (Module $module) {
  $ret = $module->execute('i32_or', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(42 | 43);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_or") (param i32) (param i32) (result i32)
        (i32.or (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_popcnt', function () {})->todo();

test('i32_reinterpret_f32', function () {})->todo();

test('i32_rem_s', function () {})->todo();

test('i32_rem_u', function () {})->todo();

test('i32_rotl', function () {})->todo();

test('i32_rotr', function () {})->todo();

test('i32_shl', function () {})->todo();

test('i32_shr_s', function () {})->todo();

test('i32_shr_u', function () {})->todo();

test('i32_store', function (Module $module) {
  $module->execute('i32_store', [new I32(42), new I32(43)]);
  $memory = $module->getMemory();
  expect($memory->data[42])->toBe(43);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (memory 1)
      (func (export "i32_store") (param i32) (param i32) (result)
        (i32.store (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_store16', function () {})->todo();

test('i32_store8', function () {})->todo();

test('i32_sub', function (Module $module) {
  // The stack is reversed, so the first value is the second operand
  $ret = $module->execute('i32_sub', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_sub") (param i32) (param i32) (result i32)
        (i32.sub (local.get 0) (local.get 1))
      )
    )
    WAT)
]);

test('i32_trunc_f32_s', function () {})->todo();

test('i32_trunc_f32_u', function () {})->todo();

test('i32_trunc_f64_s', function () {})->todo();

test('i32_trunc_f64_u', function () {})->todo();

test('i32_wrap_i64', function () {})->todo();

test('i32_xor', function (Module $module) {
  $ret = $module->execute('i32_xor', [new I32(42), new I32(43)]);
  expect($ret[0]->value)->toBe(42 ^ 43);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "i32_xor") (param i32) (param i32) (result i32)
        (i32.xor (local.get 0) (local.get 1))
      )
    )
    WAT)
]);
