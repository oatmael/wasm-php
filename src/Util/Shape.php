<?php

namespace Oatmael\WasmPhp\Util;

// The ways a v128 can be split into lanes
enum Shape {
    case I8x16;
    case I16x8;
    case I32x4;
    case I64x2;
    case F32x4;
    case F64x2;

    public function count(): int
    {
        return intdiv(128, $this->bits());
    }

    public function bits(): int
    {
        return match ($this) {
            self::I8x16                => 8,
            self::I16x8                => 16,
            self::I32x4, self::F32x4   => 32,
            self::I64x2, self::F64x2   => 64,
        };
    }

    public function isFloat(): bool
    {
        return $this === self::F32x4 || $this === self::F64x2;
    }

    // Little-endian pack() format of a single lane
    public function format(): string
    {
        return match ($this) {
            self::I8x16 => 'C',
            self::I16x8 => 'v',
            self::I32x4 => 'V',
            self::I64x2 => 'P',
            self::F32x4 => 'g',
            self::F64x2 => 'e',
        };
    }
}
