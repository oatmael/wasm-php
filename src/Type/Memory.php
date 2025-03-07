<?php

namespace Oatmael\WasmPhp\Type;

class Memory {

    public const PAGE_SIZE = 65536;
    public array $data;

    public function __construct(
        public readonly int $min,
        public readonly ?int $max,
    )
    {
        $this->init();
    }

    public function init()
    {
        $this->data = array_fill(0, $this->min * self::PAGE_SIZE, 0);
    }
}