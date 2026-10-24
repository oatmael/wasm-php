<?php

use Brick\Math\BigInteger;
use Oatmael\WasmPhp\Type\I64;

test('converts to BigInteger', function () {
    expect((string) new I64(-1)->toBigInteger())->toBe('-1');
    expect((string) new I64(-1)->toBigInteger(unsigned: true))->toBe('18446744073709551615');
    expect((string) new I64(PHP_INT_MIN)->toBigInteger(unsigned: true))->toBe('9223372036854775808');
    expect((string) new I64(42)->toBigInteger(unsigned: true))->toBe('42');
});

test('wraps BigIntegers into the i64 range', function (string $value, int $expected) {
    expect(I64::fromBigInteger(BigInteger::of($value))->value)->toBe($expected);
})->with([
    ['42', 42],
    ['-42', -42],
    ['9223372036854775808', PHP_INT_MIN],
    ['18446744073709551615', -1],
    ['18446744073709551621', 5],
    ['-9223372036854775809', PHP_INT_MAX],
]);
