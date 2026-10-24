<?php

namespace Tests\Feature\ExtensionOpcode;

use Oatmael\WasmPhp\Exception\TrapException;
use Oatmael\WasmPhp\Type\I32;

test('data_drop', function () {
  $module = wat2module(<<<WAT
    (module
      (memory 1)
      (data \$passive "hello")
      (func (export "drop") (data.drop \$passive))
      (func (export "init") (param i32)
        (memory.init \$passive (i32.const 0) (i32.const 0) (local.get 0))
      )
    )
    WAT);

  $module->execute('init', [new I32(5)]);
  $module->execute('drop', []);

  // A dropped segment is empty, so only zero-length copies succeed
  $module->execute('init', [new I32(0)]);
  expect(fn () => $module->execute('init', [new I32(1)]))->toThrow(TrapException::class, 'out of bounds memory access');
});
