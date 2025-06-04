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

    $ret = $module->execute('reverseSub', [$left, $right]);

    // TODO: write actual tests here
    expect($ret[0]->value)->toBe($right->value - $left->value);
});

test('importAdd', function (Module $module) {
    $left = new I32(4);
    $right = new I32(26);

    $ret = $module
        ->setImport('env', 'add', function (Store $store, I32 $left) use ($right) {
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