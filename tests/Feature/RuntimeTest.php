<?php

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;

test('initialises data segments', function () {
    $module = wat2module(<<<WAT
        (module
            (memory 1)
            (data (i32.const 8) "hi")
            (func (export "load") (param i32) (result i32)
                (i32.load8_u (local.get 0))
            )
        )
        WAT);

    expect($module->execute('load', [new I32(8)])[0]->getValue())->toBe(ord('h'));
    expect($module->execute('load', [new I32(9)])[0]->getValue())->toBe(ord('i'));
    expect($module->execute('load', [new I32(10)])[0]->getValue())->toBe(0);
});

test('returns multiple values in order', function (Module $module) {
    $ret = $module->execute('pair', []);
    expect(array_map(fn ($value) => $value->getValue(), $ret))->toBe([1, 2]);

    // Results passed between frames must keep their order: 1 - 2
    expect($module->execute('sub_pair', [])[0]->getValue())->toBe(-1);
})->with([
    'module' => fn () => wat2module(<<<WAT
        (module
            (func \$pair (export "pair") (result i32 i32)
                (i32.const 1)
                (i32.const 2)
            )
            (func (export "sub_pair") (result i32)
                (call \$pair)
                (i32.sub)
            )
        )
        WAT),
]);

test('executes exported imports', function () {
    $module = wat2module(<<<WAT
        (module
            (func (export "seven") (import "env" "seven") (result i32))
        )
        WAT);

    $module->setImport('env', 'seven', fn () => new I32(7));

    expect($module->execute('seven', [])[0]->getValue())->toBe(7);
});

test('rejects unknown exports', function () {
    $module = wat2module('(module)');
    $module->execute('missing', []);
})->throws(Exception::class, "No exported function named 'missing'");

test('rejects bad export arguments', function () {
    $module = wat2module(<<<WAT
        (module
            (func (export "f") (param i32))
        )
        WAT);

    $module->execute('f', []);
})->throws(Exception::class, 'Bad export call');

test('runs the else branch of an if inside a block', function (Module $module) {
    expect($module->execute('choose', [new I32(1)])[0]->getValue())->toBe(10);
    expect($module->execute('choose', [new I32(0)])[0]->getValue())->toBe(21);
})->with([
    'module' => fn () => wat2module(<<<WAT
        (module
            (func (export "choose") (param i32) (result i32)
                (local i32)
                (block
                    (if (local.get 0)
                        (then (local.set 1 (i32.const 10)))
                        (else (local.set 1 (i32.const 20)))
                    )
                    (br_if 0 (local.get 0))
                    (local.set 1 (i32.add (local.get 1) (i32.const 1)))
                )
                (local.get 1)
            )
        )
        WAT),
]);

test('unwinds the operand stack when branching', function (Module $module) {
    expect($module->execute('unwind', [])[0]->getValue())->toBe(8);
    expect($module->execute('loop_sum', [new I32(4)])[0]->getValue())->toBe(1010);
})->with([
    'module' => fn () => wat2module(<<<WAT
        (module
            ;; 10 - 2, the 1 must be discarded by the branch
            (func (export "unwind") (result i32)
                (i32.const 10)
                (block (result i32)
                    (i32.const 1)
                    (i32.const 2)
                    (br 0)
                )
                (i32.sub)
            )
            ;; Sums n + (n - 1) + ... + 1 + 1000, leaving a stray value on the stack each iteration
            (func (export "loop_sum") (param i32) (result i32)
                (local i32)
                (i32.const 1000)
                (loop \$continue
                    (i32.const 99)
                    (local.set 1 (i32.add (local.get 1) (local.get 0)))
                    (local.set 0 (i32.sub (local.get 0) (i32.const 1)))
                    (br_if \$continue (local.get 0))
                    (drop)
                )
                (local.get 1)
                (i32.add)
            )
        )
        WAT),
]);

test('branches to the function label', function () {
    $module = wat2module(<<<WAT
        (module
            (func (export "f") (result i32)
                (i32.const 5)
                (br 0)
            )
        )
        WAT);

    expect($module->execute('f', [])[0]->getValue())->toBe(5);
});
