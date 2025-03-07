<?php

use Oatmael\WasmPhp\Util\WasmReader;

test('leb128uint32', function () {
    $offset = 0;
    $hex = "05";
    $result = WasmReader::readLEB128Uint32($hex, $offset);
    expect($result)->toBe(5);
    expect($offset)->toBe(strlen($hex));
    
    $offset = 0;
    $hex = "E58E26";
    $result = WasmReader::readLEB128Uint32($hex, $offset);
    expect($result)->toBe(624485);
    expect($offset)->toBe(strlen($hex));
});

test('leb128int32', function () {
    $offset = 0;
    $hex = "05";
    $result = WasmReader::readLEB128int32($hex, $offset);
    expect($result)->toBe(5);
    expect($offset)->toBe(strlen($hex));
    
    $offset = 0;
    $hex = "9BF159";
    $result = WasmReader::readLEB128int32($hex, $offset);
    expect($result)->toBe(-624485);
    expect($offset)->toBe(strlen($hex));
});
