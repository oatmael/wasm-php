<?php

namespace Oatmael\WasmPhp\Type;

use Oatmael\WasmPhp\Util\ValueType;

class GlobalType {
    public function __construct(
        public readonly ValueType|RefType $type,
        public readonly bool $mutable,
        // The constant expression a defined global is initialised with. Imported globals have none.
        public readonly array $init = [],
    )
    {
    }
}
