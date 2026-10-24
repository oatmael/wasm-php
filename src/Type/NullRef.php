<?php

namespace Oatmael\WasmPhp\Type;

// The null reference. Nulls of every heap type behave the same at runtime, so they aren't distinguished.
class NullRef implements ValueInterface {
    public function getValue()
    {
        return null;
    }
}
