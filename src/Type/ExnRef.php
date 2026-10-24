<?php

namespace Oatmael\WasmPhp\Type;

// A thrown exception: the tag it was thrown with, and its arguments
class ExnRef implements ValueInterface {
    public function __construct(
        public readonly Tag $tag,
        public readonly array $values,
    )
    {
    }

    public function getValue()
    {
        return $this->values;
    }
}
