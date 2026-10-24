<?php

namespace Oatmael\WasmPhp\Type;

// A function type. Like the other composite types, it can declare a supertype and whether it is final.
class Func {
    public function __construct(
        public readonly array $params,
        public readonly array $results,
        public readonly array $supertypes = [],
        public readonly bool $final = true,
    )
    {
    }
}
