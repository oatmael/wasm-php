<?php

namespace Tests\Feature\StandardOpcode;

use Oatmael\WasmPhp\Exception\CallStackExceededException;
use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Exception\UncaughtException;
use Oatmael\WasmPhp\Exception\UnreachableException;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\ExnRef;
use Oatmael\WasmPhp\Type\FuncRef;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\NullRef;

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
  expect($ret[0]->value)->toBe(102);

  $ret = $module->execute('table', [new I32(1)]);
  expect($ret[0]->value)->toBe(101);

  $ret = $module->execute('table', [new I32(2)]);
  expect($ret[0]->value)->toBe(100);

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
            ;; Target for (br \$d)
            (i32.const 100)
            (return))
          ;; Target for (br \$c)
          (i32.const 101)
          (return))
        ;; Target for (br \$b)
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

test('call_indirect', function (Module $module) {
  expect($module->execute('call', [new I32(0), new I32(5)])[0]->getValue())->toBe(6);
  expect($module->execute('call', [new I32(1), new I32(5)])[0]->getValue())->toBe(10);

  expect(fn () => $module->execute('call', [new I32(2), new I32(5)]))->toThrow(TrapException::class, 'indirect call type mismatch');
  expect(fn () => $module->execute('call', [new I32(3), new I32(5)]))->toThrow(TrapException::class, 'uninitialized element');
  expect(fn () => $module->execute('call', [new I32(4), new I32(5)]))->toThrow(TrapException::class, 'undefined element');
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (type \$unary (func (param i32) (result i32)))
      (table 4 funcref)
      (elem (i32.const 0) \$inc \$double \$void)
      (func \$inc (param i32) (result i32) (i32.add (local.get 0) (i32.const 1)))
      (func \$double (param i32) (result i32) (i32.mul (local.get 0) (i32.const 2)))
      (func \$void)
      (func (export "call") (param i32 i32) (result i32)
        (call_indirect (type \$unary) (local.get 1) (local.get 0))
      )
    )
    WAT),
]);

test('call_ref', function (Module $module) {
  expect($module->execute('call', [new I32(4)])[0]->getValue())->toBe(5);
  expect(fn () => $module->execute('call_null', []))->toThrow(TrapException::class, 'null function reference');
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (type \$unary (func (param i32) (result i32)))
      (func \$inc (type \$unary) (i32.add (local.get 0) (i32.const 1)))
      (elem declare func \$inc)
      (func (export "call") (param i32) (result i32)
        (call_ref \$unary (local.get 0) (ref.func \$inc))
      )
      (func (export "call_null") (result i32)
        (call_ref \$unary (i32.const 0) (ref.null \$unary))
      )
    )
    WAT),
]);

test('return_call', function () {
  $module = wat2module(<<<WAT
    (module
      ;; Counts down without growing the call stack
      (func \$count (export "count") (param i64) (result i64)
        (if (result i64) (i64.eqz (local.get 0))
          (then (i64.const 42))
          (else (return_call \$count (i64.sub (local.get 0) (i64.const 1))))
        )
      )
      (func \$deep (export "deep") (param i64) (result i64)
        (if (result i64) (i64.eqz (local.get 0))
          (then (i64.const 42))
          (else (call \$deep (i64.sub (local.get 0) (i64.const 1))))
        )
      )
    )
    WAT);

  expect($module->execute('count', [new I64(Module::MAX_CALL_STACK * 2)])[0]->getValue())->toBe(42);
  expect(fn () => $module->execute('deep', [new I64(Module::MAX_CALL_STACK * 2)]))->toThrow(CallStackExceededException::class);
});

test('return_call_indirect', function () {
  $module = wat2module(<<<WAT
    (module
      (type \$t (func (param i32) (result i32)))
      (table funcref (elem \$even \$odd))
      (func \$even (type \$t)
        (if (result i32) (i32.eqz (local.get 0))
          (then (i32.const 1))
          (else (return_call_indirect (type \$t) (i32.sub (local.get 0) (i32.const 1)) (i32.const 1)))
        )
      )
      (func \$odd (type \$t)
        (if (result i32) (i32.eqz (local.get 0))
          (then (i32.const 0))
          (else (return_call_indirect (type \$t) (i32.sub (local.get 0) (i32.const 1)) (i32.const 0)))
        )
      )
      (func (export "is_even") (param i32) (result i32) (call \$even (local.get 0)))
    )
    WAT);

  expect($module->execute('is_even', [new I32(30_001)])[0]->getValue())->toBe(0);
  expect($module->execute('is_even', [new I32(30_000)])[0]->getValue())->toBe(1);
});

test('return_call_ref', function () {
  $module = wat2module(<<<WAT
    (module
      (type \$t (func (param i32) (result i32)))
      (func \$sum (type \$t)
        (if (result i32) (i32.eqz (local.get 0))
          (then (i32.const 0))
          (else (return_call_ref \$t (i32.sub (local.get 0) (i32.const 1)) (ref.func \$sum)))
        )
      )
      (elem declare func \$sum)
      (func (export "f") (param i32) (result i32) (call \$sum (local.get 0)))
    )
    WAT);

  expect($module->execute('f', [new I32(30_000)])[0]->getValue())->toBe(0);
});

test('br_on_null', function (Module $module) {
  expect($module->execute('f', [new NullRef()])[0]->getValue())->toBe(-1);
  expect($module->execute('f', [$module->getExport('seven')])[0]->getValue())->toBe(7);
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (type \$t (func (result i32)))
      (func (export "seven") (type \$t) (i32.const 7))
      (func (export "f") (param (ref null \$t)) (result i32)
        (block \$null
          (return (call_ref \$t (br_on_null \$null (local.get 0))))
        )
        (i32.const -1)
      )
    )
    WAT),
]);

test('br_on_non_null', function (Module $module) {
  expect($module->execute('f', [new NullRef()])[0]->getValue())->toBe(-1);
  expect($module->execute('f', [$module->getExport('seven')])[0]->getValue())->toBe(7);
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (type \$t (func (result i32)))
      (func (export "seven") (type \$t) (i32.const 7))
      (func (export "f") (param (ref null \$t)) (result i32)
        (block \$non_null (result (ref \$t))
          (br_on_non_null \$non_null (local.get 0))
          (return (i32.const -1))
        )
        (call_ref \$t)
      )
    )
    WAT),
]);

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
  $ret = $module->execute('nop', [new I32(7)]);
  expect($ret[0]->getValue())->toBe(7);
})->with([
  'module' => fn() => wat2module(<<<WAT
    (module
      (func (export "nop") (param i32) (result i32)
        nop
        local.get 0
        nop
        nop
      )
    )
    WAT)
]);

test('select', function (Module $module) {
  // A non-zero condition selects the first operand
  $ret = $module->execute('select', [new I32(0)]);
  expect($ret[0]->value)->toBe(2);

  $ret = $module->execute('select', [new I32(1)]);
  expect($ret[0]->value)->toBe(1);
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

test('select_t', function () {
  $module = wat2module(<<<WAT
    (module
      (func (export "f") (param externref externref i32) (result externref)
        (select (result externref) (local.get 0) (local.get 1) (local.get 2))
      )
    )
    WAT);

  $first = new \Oatmael\WasmPhp\Type\ExternRef('first');
  $second = new \Oatmael\WasmPhp\Type\ExternRef('second');
  expect($module->execute('f', [$first, $second, new I32(1)])[0])->toBe($first);
  expect($module->execute('f', [$first, $second, new I32(0)])[0])->toBe($second);
});

test('throw', function (Module $module) {
  $exception = null;
  try {
    $module->execute('throw', [new I32(42)]);
  } catch (UncaughtException $e) {
    $exception = $e->exception;
  }

  expect($exception)->toBeInstanceOf(ExnRef::class);
  expect($exception->values[0]->getValue())->toBe(42);
  expect($exception->tag)->toBe($module->getExport('e'));
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (tag \$e (export "e") (param i32))
      (func (export "throw") (param i32) (throw \$e (local.get 0)))
    )
    WAT),
]);

test('try_table', function (Module $module) {
  // catch branches with the exception's values
  expect($module->execute('catch', [new I32(1)])[0]->getValue())->toBe(42);
  expect($module->execute('catch', [new I32(0)])[0]->getValue())->toBe(0);

  // Exceptions unwind through calls, and catch_all handles any tag
  expect($module->execute('catch_all', [])[0]->getValue())->toBe(1);

  // Traps can't be caught
  expect(fn () => $module->execute('trap', []))->toThrow(UnreachableException::class);
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (tag \$e (param i32))
      (tag \$other)
      (func \$thrower (throw \$other))
      (func (export "catch") (param i32) (result i32)
        (block \$handler (result i32)
          (try_table (catch \$e \$handler)
            (if (local.get 0) (then (throw \$e (i32.const 42))))
          )
          (i32.const 0)
        )
      )
      (func (export "catch_all") (result i32)
        (block \$handler
          (try_table (catch \$e 0) (catch_all \$handler)
            (call \$thrower)
          )
          (return (i32.const 0))
        )
        (i32.const 1)
      )
      (func (export "trap") (result i32)
        (block \$handler
          (try_table (catch_all \$handler)
            (unreachable)
          )
        )
        (i32.const 0)
      )
    )
    WAT),
]);

test('throw_ref', function (Module $module) {
  // catch_ref passes the exception, so it can be rethrown and caught again
  expect($module->execute('rethrow', [])[0]->getValue())->toBe(7);
  expect(fn () => $module->execute('throw_null', []))->toThrow(TrapException::class, 'null exception reference');
})->with([
  'module' => fn () => wat2module(<<<WAT
    (module
      (tag \$e (param i32))
      (func (export "rethrow") (result i32)
        (block \$outer (result i32)
          (try_table (catch \$e \$outer)
            (block \$inner (result i32 exnref)
              (try_table (catch_ref \$e \$inner)
                (throw \$e (i32.const 7))
              )
              (unreachable)
            )
            (throw_ref)
          )
          (unreachable)
        )
      )
      (func (export "throw_null") (throw_ref (ref.null exn)))
    )
    WAT),
]);

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
