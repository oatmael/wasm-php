<?php

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\WasmReader;

test('add', function () {
    $wasm = file_get_contents('examples/add.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $left = new I32(4);
    $right = new I32(26);

    $ret = $module->execute('addTwo', [$left, $right]);

    // TODO: write actual tests here
    expect($ret[0]->value)->toBe($left->value + $right->value);
});

test('reverseSub', function () {
    $wasm = file_get_contents('examples/reverseSub.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $left = new I32(4);
    $right = new I32(26);

    $ret = $module->execute('reverseSub', [$right, $left]);

    // TODO: write actual tests here
    expect($ret[0]->value)->toBe($right->value - $left->value);
});

test('importAdd', function (Module $module) {
    $left = new I32(4);
    $right = new I32(26);

    $ret = $module
        ->setImport('env', 'add', function ($stack, $call_stack, Store $store, I32 $left) use ($right) {
            return new I32($left->value + $right->value);
        })
        ->execute('call_add', [$left]);

    expect($ret[0]->value)->toBe($right->value + $left->value);
})->with([
    'importAddModule' => fn() => wat2module(<<<WAT
        (module
            (func \$add (import "env" "add") (param i32) (result i32))
            (func (export "call_add") (param i32) (result i32)
                (local.get 0)
                (call \$add)
            )
        )
        WAT)
]);

test('memory', function () {
    $wasm = file_get_contents('examples/memory.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $ret = $module->execute('i32_store', []);

    // TODO: write actual tests here
    expect($ret)->toBeEmpty();
});

test('data', function () {
    $wasm = file_get_contents('examples/data.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $ret = [];
    // $ret = $module->execute('i32_store', []);

    // TODO: write actual tests here
    expect($ret)->toBeEmpty();
});

test('globals', function () {
    $wasm = file_get_contents('examples/globals.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $ret = $module->execute('addTwo', [new I32(5)]);

    // TODO: write actual tests here
    expect($ret[0]->value)->toBe(31);
});

test('start', function () {
    $wasm = file_get_contents('examples/start.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);
    $module->setImport('env', 'init', function () {
        var_dump('This is the start function');
    });

    $ret = $module->execute('addTwo', [new I32(4), new I32(26)]);

    // TODO: write actual tests here
    expect($ret[0]->value)->toBe(30);
});

test('fibonacci', function (Module $module) {
    $fib = [
        0,
        1,
        1,
        2,
        3,
        5,
        8,
        13,
        21,
        34,
        55,
        89,
        144,
        233,
        377,
        610,
        987,
        1597,
        2584,
        4181,
        6765,
        10946,
        17711,
        28657,
        46368,
        75025,
        121393,
        196418,
        317811,
        514229,
        832040,
        1346269,
        2178309,
        3524578,
        5702887,
        9227465,
        14930352,
        24157817,
        39088169,
        63245986,
        102334155,
        165580141,
        267914296,
        433494437,
        701408733,
        1134903170,
        1836311903,
        2971215073,
    ];

    $ret = $module->execute('fib', [new I32(1)]);
    expect($ret[0]->value)->toBe($fib[1]);

    $ret = $module->execute('fib', [new I32(10)]);
    expect($ret[0]->value)->toBe($fib[10]);

    // fib(25) is far enough into the sequence that the 24 repeating pattern comes into play, any further slows down the test suite considerably
    $ret = $module->execute('fib', [new I32(25)]);
    expect($ret[0]->value)->toBe($fib[25]);
})->with([
    'module' => fn() => wat2module(<<<WAT
    (module
    (type (;0;) (func (param i32) (result i32)))
    (func \$fib (type 0) (param \$n i32) (result i32)
        local.get \$n
        i32.const 2
        i32.le_s
        if  ;; label = @1
        i32.const 1
        return
        end
        local.get \$n
        i32.const 2
        i32.sub
        call \$fib
        local.get \$n
        i32.const 1
        i32.sub
        call \$fib
        i32.add
        return)
    (export "fib" (func \$fib)))
    WAT),
]);

test('fibonnaci_loop', function (Module $module) {
    $fib = [
        0,
        1,
        1,
        2,
        3,
        5,
        8,
        13,
        21,
        34,
        55,
        89,
        144,
        233,
        377,
        610,
        987,
        1597,
        2584,
        4181,
        6765,
        10946,
        17711,
        28657,
        46368,
        75025,
        121393,
        196418,
        317811,
        514229,
        832040,
        1346269,
        2178309,
        3524578,
        5702887,
        9227465,
        14930352,
        24157817,
        39088169,
        63245986,
        102334155,
        165580141,
        267914296,
        433494437,
        701408733,
        1134903170,
        1836311903,
        2971215073,
    ];

    $ret = $module->execute('fib', [new I32(47)]);
    expect($ret[0]->value)->toBe($fib[47]);
})->with([
    'module' => fn() => wat2module(<<<WAT
    (module
    (type (;0;) (func (param i32) (result i32)))
    (func (;0;) (type 0) (param \$n i32) (result i32)
        (local \$i i32) (local \$prev i32) (local \$curr i32) (local \$next i32)
        local.get \$n
        i32.const 2
        i32.lt_s
        if  ;; label = @1
            local.get \$n
            return
        end
        i32.const 0
        local.set \$prev
        i32.const 1
        local.set \$curr
        i32.const 1
        local.set \$i
        loop  ;; label = @1
            local.get \$prev
            local.get \$curr
            i32.add
            local.set \$next
            local.get \$curr
            local.set \$prev
            local.get \$next
            local.set \$curr
            local.get \$i
            i32.const 1
            i32.add
            local.set \$i
            local.get \$i
            local.get \$n
            i32.lt_s
            br_if 0 (;@1;)
        end
        local.get \$curr)
    (export "fib" (func 0)))
    WAT),
]);