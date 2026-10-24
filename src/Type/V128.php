<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\Shape;

// A 128-bit vector, stored as 16 little-endian bytes and split into lanes as instructions need them
class V128 implements ValueInterface {
    public function __construct(
        public readonly string $bytes,
    )
    {
    }

    public function getValue(): string
    {
        return $this->bytes;
    }

    // Integer lanes are unsigned unless $signed is set. 64-bit lanes are always signed, as PHP ints are.
    public function lanes(Shape $shape, bool $signed = false): array
    {
        $lanes = array_values(unpack($shape->format() . $shape->count(), $this->bytes));
        if (!$signed || $shape->isFloat() || $shape === Shape::I64x2) {
            return $lanes;
        }

        $shift = 64 - $shape->bits();
        return array_map(static fn (int $lane) => ($lane << $shift) >> $shift, $lanes);
    }

    // Integer lanes wrap to the lane width, and float lanes round to the lane's precision
    public static function fromLanes(Shape $shape, array $lanes): self
    {
        return new self(pack($shape->format() . $shape->count(), ...$lanes));
    }
}
