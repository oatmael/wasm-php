<?php

namespace Oatmael\WasmPhp\Type;

interface ValueInterface {
    public function getUSize(): int;
    public function getValue();
}