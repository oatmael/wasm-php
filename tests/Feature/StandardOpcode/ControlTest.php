<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Execution\UnreachableException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;

test('block', function (Module $module) {
  $ret = $module->execute('block', []);
  expect($ret)->toBeEmpty();
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "block") (param) (result)
        (block \$block
          br \$block
          unreachable
        )
    ))
    WAT)
]);

test('br', function (Module $module) {
  $ret = $module->execute('block', []);
  expect($ret)->toBeEmpty();
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "block") (param) (result)
        (block \$block
          br \$block
          unreachable
        )
    ))
    WAT)
]);

test('br_if', function (Module $module) {
  $ret = $module->execute('block_branch', []);
  expect($ret)->toBeEmpty();

  expect(fn() => $module->execute('block_no_branch', []))->toThrow(UnreachableException::class);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "block_branch") (param) (result)
        (block \$block
          i32.const 1
          br_if \$block
          unreachable
        )
      )
      (func (export "block_no_branch") (param) (result)
        (block \$block
          i32.const 0
          br_if \$block
          unreachable
        )
      )
    )
    WAT)
]);

test('br_table', function (Module $module) {
  $ret = $module->execute('table', [new I32(0)]);
  expect($ret[0]->value)->toBe(100);

  $ret = $module->execute('table', [new I32(1)]);
  expect($ret[0]->value)->toBe(101);

  $ret = $module->execute('table', [new I32(2)]);
  expect($ret[0]->value)->toBe(102);

  $ret = $module->execute('table', [new I32(3)]);
  expect($ret[0]->value)->toBe(103);

  $ret = $module->execute('table', [new I32(4)]);
  expect($ret[0]->value)->toBe(103);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "table") (param \$p i32) (result i32)
      (block \$a
        (block \$b
          (block \$c
            (block \$d (local.get \$p)
              (br_table
                \$b   ;; p == 0 => (br 2)
                \$c   ;; p == 1 => (br 1)
                \$d   ;; p == 2 => (br 0)
                \$a)) ;; else => (br 3)
            ;; Target for (br \$b)
            (i32.const 100)
            (return))
          ;; Target for (br \$c)
          (i32.const 101)
          (return))
        ;; Target for (br \$d)
        (i32.const 102)
        (return))
      ;; Target for (br \$a)
      (i32.const 103)
      (return))
    )
    WAT),
]);

test('call', function (Module $module) {
  $ret = $module->execute('call', []);
  expect($ret[0]->value)->toBe(1);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func \$one (param) (result i32)
        (i32.const 1)
      )
      (func (export "call") (param) (result i32)
        (call \$one)
      )
    )
    WAT)
]);

test('call_indirect', function () {})->todo();

test('drop', function (Module $module) {
  $ret = $module->execute('drop', [new I32(1)]);
  expect($ret)->toBeEmpty();
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "drop") (param i32) (result)
        local.get 0
        drop
      )
    )
    WAT)
]);

test('end', function (Module $module) {
  $ret = $module->execute('end', []);
  expect($ret)->toBeEmpty();
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "end") (param) (result)
        (block) ;; `end` is an implied opcode - it isn't present in WAT, but it marks the end of blocks and functions.
      )
    )
    WAT)
]);

test('else', function (Module $module) {
  $ret = $module->execute('else', [new I32(0)]);
  expect($ret[0]->value)->toBe(20);

  $ret = $module->execute('else', [new I32(1)]);
  expect($ret[0]->value)->toBe(10);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "else") (param i32) (result i32)
        local.get 0
        (if (result i32)
          (then
            i32.const 10
          )
          (else
            i32.const 20
          )
        )
      )
    )
    WAT)
]);

test('if', function (Module $module) {
  $ret = $module->execute('if', [new I32(0)]);
  expect($ret[0]->value)->toBe(0);

  $ret = $module->execute('if', [new I32(1)]);
  expect($ret[0]->value)->toBe(10);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "if") (param i32) (result i32)
        local.get 0
        (if
          (then
            i32.const 10
            local.set 0
          )
        )
        local.get 0
      )
    )
    WAT)
]);

test('return', function (Module $module) {
  $ret = $module->execute('return', []);
  expect($ret)->toBeEmpty();
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "return") (param) (result)
        return
        unreachable
      )
    )
    WAT)
]);

test('loop', function (Module $module) {
  $ret = $module->execute('loop', []);
  expect($ret[0]->value)->toBe(10);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "loop") (param) (result i32)
        (local \$i i32)
        (loop \$loop
          local.get \$i
          i32.const 1
          i32.add
          local.set \$i

          local.get \$i
          i32.const 10
          i32.ne
          br_if \$loop
        )
        local.get \$i
      )
    )
    WAT)
]);

test('nop', function (Module $module) {
  $module->setImport('env', 'test_pc', fn($stack, $call_stack) => expect(end($call_stack)->program_counter)->toBe(4));
  $module->execute('nop', []);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (import "env" "test_pc" (func \$test_pc))
      (func (export "nop") (result)
        nop
        nop
        nop
        nop
        call \$test_pc
      )
    )
    WAT)
]);

test('select', function (Module $module) {
  $ret = $module->execute('select', [new I32(0)]);
  expect($ret[0]->value)->toBe(1);

  $ret = $module->execute('select', [new I32(1)]);
  expect($ret[0]->value)->toBe(2);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "select") (param i32) (result i32)
        i32.const 1
        i32.const 2
        local.get 0
        select
      )
    )
    WAT)
]);

test('select_t', function () {})->todo();

test('unreachable', function (Module $module) {
  expect(fn() => $module->execute('unreachable', []))->toThrow(UnreachableException::class);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "unreachable") (result i32)
        (unreachable)
      )
    )
    WAT)
]);
