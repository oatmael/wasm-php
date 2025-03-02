<?php

use Oatmael\WasmPhp\Util\WasmReader;

test('load', function() {
    $wasm = file_get_contents('examples/add.wasm');

    $util = new WasmReader($wasm);
    $module = $util->read();

    // TODO: write actual tests here
    expect($module)->toBeString();
});