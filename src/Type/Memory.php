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

    public function grow(int $pages): int
    {
        if ((count($this->data) + ($pages * self::PAGE_SIZE)) > (($this->max ? $this->max : self::PAGE_SIZE) * self::PAGE_SIZE)) {
            return -1;
        }

        $previous_pages = count($this->data) / self::PAGE_SIZE;
        array_splice($this->data, count($this->data), $pages * self::PAGE_SIZE, array_fill(0, $pages * self::PAGE_SIZE, 0));

        return $previous_pages;
    }
}