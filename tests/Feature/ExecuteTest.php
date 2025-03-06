<?php

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
    expect($ret->value)->toBe($left->value + $right->value);
});

test('reverseSub', function() {
    $wasm = file_get_contents('examples/reverseSub.wasm');

    $util = new WasmReader();
    $module = $util->read($wasm);

    $left = new I32(4);
    $right = new I32(26);

    $ret = $module->execute('reverseSub', [$left, $right]);

    // TODO: write actual tests here
    expect($ret->value)->toBe($right->value - $left->value);
});