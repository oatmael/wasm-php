<?php

use Oatmael\WasmPhp\Module;
use Oatmael\WasmPhp\Type\I32;

test('add', function () {
    $module = wat2module(<<<WAT
        (module
            (func (export "addTwo") (param i32 i32) (result i32)
                local.get 0
                local.get 1
                i32.add
            )
        )
        WAT);

    $ret = $module->execute('addTwo', [new I32(4), new I32(26)]);

    expect($ret[0]->getValue())->toBe(30);
});

test('reverseSub', function () {
    $module = wat2module(<<<WAT
        (module
            (func \$swap (param i32 i32) (result i32 i32)
                local.get 1
                local.get 0
            )
            (func (export "reverseSub") (param i32 i32) (result i32)
                local.get 0
                local.get 1
                call \$swap
                i32.sub
            )
        )
        WAT);

    $ret = $module->execute('reverseSub', [new I32(26), new I32(4)]);

    expect($ret[0]->getValue())->toBe(4 - 26);
});

test('importAdd', function (Module $module) {
    $left = new I32(4);
    $right = new I32(26);

    $ret = $module
        ->setImport('env', 'add', fn (int $left) => $left + $right->getValue())
        ->execute('call_add', [$left]);

    expect($ret[0]->value)->toBe($right->value + $left->value);
})->with([
    'module' => fn() => wat2module(<<<WAT
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
    $module = wat2module(<<<WAT
        (module
            (memory 1)
            (func (export "i32_store")
                i32.const 0
                i32.const 42
                i32.store
            )
        )
        WAT);

    $ret = $module->execute('i32_store', []);

    expect($ret)->toBeNull();
    expect(array_slice($module->getMemory()->data, 0, 4))->toBe([42, 0, 0, 0]);
});

test('data', function () {
    $module = wat2module(<<<WAT
        (module
            (memory 1)
            (data (i32.const 0) "hello")
            (data (i32.const 5) "world")
            (func (export "noop"))
        )
        WAT);

    $module->execute('noop', []);

    expect(array_slice($module->getMemory()->data, 0, 10))->toBe(array_map(ord(...), str_split('helloworld')));
});

test('globals', function () {
    $module = wat2module(<<<WAT
        (module
            (global \$a (mut i32) (i32.const 4))
            (global \$b i32 (i32.const 26))
            (func (export "addTwo") (param i32) (result i32)
                local.get 0
                global.set \$a
                global.get \$a
                global.get \$b
                i32.add
            )
        )
        WAT);

    $ret = $module->execute('addTwo', [new I32(5)]);

    expect($ret[0]->getValue())->toBe(31);
});

test('start', function () {
    $module = wat2module(<<<WAT
        (module
            (import "env" "init" (func \$init))
            (func (export "addTwo") (param i32 i32) (result i32)
                local.get 0
                local.get 1
                i32.add
            )
            (start \$init)
        )
        WAT);

    $started = 0;
    $module->setImport('env', 'init', function () use (&$started) {
        $started++;
    });

    $ret = $module->execute('addTwo', [new I32(4), new I32(26)]);
    $module->execute('addTwo', [new I32(4), new I32(26)]);

    expect($ret[0]->getValue())->toBe(30);
    expect($started)->toBe(1);
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
    ];

    $ret = $module->execute('fib', [new I32(1)]);
    expect($ret[0]->getValue())->toBe($fib[1]);

    $ret = $module->execute('fib', [new I32(10)]);
    expect($ret[0]->getValue())->toBe($fib[10]);

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
        // At this point, fib(n) will experience overflows
        -1323752223,
        512559680,
        -811192543,
        -298632863,
        -1109825406,
        -1408458269,
        1776683621,
        368225352,
        2144908973,
        -1781832971,
        363076002,
        -1418756969,
        -1055680967,
        1820529360,
        764848393,
        -1709589543,
        -944741150,
        1640636603,
        695895453,
        -1958435240,
        -1262539787,
        1073992269,
        -188547518,
        885444751,
        696897233,
        1582341984,
        -2015728079,
        -433386095,
        1845853122,
        1412467027,
        -1036647147,
        375819880,
        -660827267,
        -285007387,
        -945834654,
        -1230842041,
        2118290601,
        887448560,
        -1289228135,
        -401779575,
        -1691007710,
        -2092787285,
        511172301,
        -1581614984,
        -1070442683,
        1642909629,
        572466946,
        -2079590721,
        -1507123775,
        708252800,
        -798870975,
        -90618175,
        -889489150,
        -980107325,
    ];

    $ret = $module->execute('fib', [new I32(47)]);
    expect($ret[0]->getValue())->toBe($fib[47]);

    for ($i = 0; $i <= 100; $i++) {
        $ret = $module->execute('fib', [new I32($i)]);
        expect($ret[0]->getValue())->toBe($fib[$i]);
    }
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