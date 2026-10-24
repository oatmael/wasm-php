<?php

use Oatmael\WasmPhp\Util\WasmReader;

test('leb128uint32', function () {
    $offset = 0;
    $bytes = hex2bin("05");
    $result = WasmReader::readLEB128Uint32($bytes, $offset);
    expect($result)->toBe(5);
    expect($offset)->toBe(strlen($bytes));
    
    $offset = 0;
    $bytes = hex2bin("E58E26");
    $result = WasmReader::readLEB128Uint32($bytes, $offset);
    expect($result)->toBe(624485);
    expect($offset)->toBe(strlen($bytes));
});

test('leb128int32', function () {
    $offset = 0;
    $bytes = hex2bin("05");
    $result = WasmReader::readLEB128Int32($bytes, $offset);
    expect($result)->toBe(5);
    expect($offset)->toBe(strlen($bytes));
    
    $offset = 0;
    $bytes = hex2bin("9BF159");
    $result = WasmReader::readLEB128Int32($bytes, $offset);
    expect($result)->toBe(-624485);
    expect($offset)->toBe(strlen($bytes));
});

test('leb128int64', function () {
    $offset = 0;
    $bytes = hex2bin("7F");
    expect(WasmReader::readLEB128Int64($bytes, $offset))->toBe(-1);

    $offset = 0;
    $bytes = hex2bin("FFFFFFFFFFFFFFFF7F");
    expect(WasmReader::readLEB128Int64($bytes, $offset))->toBe(-1);
    expect($offset)->toBe(strlen($bytes));

    $offset = 0;
    $bytes = hex2bin("FFFFFFFFFFFFFFFFFF00");
    expect(WasmReader::readLEB128Int64($bytes, $offset))->toBe(PHP_INT_MAX);
    expect($offset)->toBe(strlen($bytes));
});

test('leb128uint32 rejects overlong values', function () {
    $offset = 0;
    WasmReader::readLEB128Uint32(hex2bin("FFFFFFFF7F"), $offset);
})->throws(Exception::class, 'LEB128 value too large for a u32');

test('leb128 rejects truncated input', function () {
    $offset = 0;
    WasmReader::readLEB128Uint32(hex2bin("80"), $offset);
})->throws(Exception::class, 'Unexpected end of input');
