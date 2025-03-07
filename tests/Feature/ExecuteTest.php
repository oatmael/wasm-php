<?php

use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Util\WasmReader;

test('add', function() {
    $wasm = file_get_contents('examples/add.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $left = new I32(4);
    $right = new I32(26);

    $ret = $module->execute('addTwo', [$left, $right]);

    // TODO: write actual tests here
    expect($ret[0]->value)->toBe($left->value + $right->value);
});

test('reverseSub', function() {
    $wasm = file_get_contents('examples/reverseSub.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $left = new I32(4);
    $right = new I32(26);

    $ret = $module->execute('reverseSub', [$left, $right]);

    // TODO: write actual tests here
    expect($ret[0]->value)->toBe($right->value - $left->value);
});

test('importAdd', function() {
    $wasm = file_get_contents('examples/importAdd.wasm');
    // (module
    //     (func $add (import "env" "add") (param i32) (result i32))
    //     (func (export "call_add") (param i32) (result i32)
    //         (local.get 0)
    //         (call $add)
    //     )
    // )

    $util = new WasmReader();
    $module = $util->read($wasm);

    $left = new I32(4);
    $right = new I32(26);

    $ret = $module
        ->setImport('env', 'add', function (Store $store, I32 $left) use ($right) {
            return new I32($left->value + $right->value);
        })
        ->execute('call_add', [$left]);

    // TODO: write actual tests here
    expect($ret[0]->value)->toBe($right->value + $left->value);
});

test('memory', function() {
    $wasm = file_get_contents('examples/memory.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $ret = $module->execute('i32_store', []);

    // TODO: write actual tests here
    expect($ret)->toBeEmpty();
});

test('data', function() {
    $wasm = file_get_contents('examples/data.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $ret = [];
    // $ret = $module->execute('i32_store', []);

    // TODO: write actual tests here
    expect($ret)->toBeEmpty();
});