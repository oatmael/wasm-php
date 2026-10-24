# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.
Allows follow DRY, YAGNI & KISS principles. YAGNI takes precedent over DRY - never abstract prematurely.

## Project

A WebAssembly runtime written in pure PHP (requires PHP ^8.5). Namespace `Oatmael\WasmPhp\` maps to `src/` (PSR-4). Depends on `brick/math` for arbitrary-precision arithmetic. Still a WIP; see the proposal status table in README.md.

## Commands

```bash
nix develop                         # dev shell with PHP 8.5, composer and WABT (or `direnv allow` with the included .envrc)
composer install
./vendor/bin/pest                   # run all tests
./vendor/bin/pest tests/Feature/StandardOpcode/i32Test.php   # one file
./vendor/bin/pest --filter=i32_add  # one test by name
```

Tests need WABT's `wat2wasm` on `PATH` (override with the `WAT2WASM` env var), because most tests compile inline WAT at runtime. `tests/Feature/ExecuteTest.php` loads `examples/*.wasm` through relative paths, so run Pest from the repo root. CI (`.github/workflows/test.yml`) installs WABT via apt and runs `./vendor/bin/pest --ci` on PHP 8.5.

## Architecture

**Decoding (`src/Util/WasmReader.php`)**: `WasmReader::read()` parses the raw binary string; all offsets are byte offsets. The static helpers (`readUint8`, `readLEB128Uint32`, `readLEB128Int32/Int64`, `readBlockType`, etc.) take `(string $input, int &$offset)` and advance the offset. Each section is read through `readVec()` into value objects in `src/Type/`, every section must consume exactly its declared size, and the result is returned as a `Module`. Global initialisers and data/element offsets are constant expressions decoded with the instruction decoder (`readConstExpr`). Only function imports and active data/element segments (flags 0/2 and 0) are supported; anything else throws a clear exception. The enums `ValueType`, `Section`, `ExportType`, etc. live in their own files in `src/Util/`.

**Opcode dispatch (`WasmReader::readInstruction()`)**: the enums `StandardOpcode`, `ExtensionOpcode` (`0xfc` prefix, subnamespace `Instruction\Extension`) and `SimdOpcode` (`0xfd` prefix, subnamespace `Instruction\Simd`) live in `src/Instruction/`. Prefixed opcodes are read as a LEB128 u32 after the prefix byte. The instruction class is derived from the enum case name (`i32_load8_s` → `I32Load8S`), except reserved words listed in `RESERVED_INSTRUCTION_NAMES` (`if` → `IIf`, `else` → `IElse`, `return` → `IReturn`). To add an instruction, create a class with the conventional name in the correct namespace that implements `InstructionInterface`.

**Instruction contract (`InstructionInterface`)**:
- `static fromInput(string $input, int &$offset)` decodes any immediates (with `WasmReader` helpers) and returns an instance.
- Memory immediates (memarg) are two LEB128 u32s: align, then offset. Block types are read with `readBlockType()` (`ValueType` or a type-section index) and resolved at runtime with `Store::resolveBlockType()`.
- `execute(array &$stack, array &$call_stack, Store $store)` changes the operand stack and call stack in place. Operands are popped in reverse order (`$right` first, then `$left`).
- Many opcodes are still stubs that throw `Exception('Not implemented: ...')`. These include all of SIMD, most I64/F64 opcodes, and most extension opcodes.

**Execution**:
- `Module::execute($name, $args)` checks the argument types against the export's signature, pushes a frame, and runs `executeFrame()`. That is a flat loop: it increments the top frame's `program_counter` (frames start at -1) and executes one instruction per step. No PHP recursion is used for wasm calls. `MAX_CALL_STACK` is 10,000.
- `Execution\Store` holds every module section, runs data-segment initialisation on first execute, and handles `pushFrame` / `popFrame`. Function indices include imports: indices below `count($imports)` are imports, and anything at or above that is an offset into `functions`/`codes`. Imported functions are PHP callables registered with `Module::setImport()`. They are called as `fn($stack, $call_stack, $store, ...$args)` and may return a value or an array of values.
- Structured control flow: `Block`/`Loop`/`IIf` scan forward for the matching `End` and push a `ControlStackEntry` (break target and type) onto the frame's `control_stack`. `Br*` pops entries and jumps. `End` pops a control entry or, if none remain, pops the frame.

**Values (`src/Type/I32|I64|F32|F64`)**: thin wrappers around PHP `int`/`float`. PHP ints are signed 64-bit, so `I32` stores an int and normalises it when read. `getValue()` sign-extends the low 32 bits, and `toUnsigned()` gives the unsigned view (used for memory addresses). Overflow and wrap behaviour is handled per instruction (README "Limitations" covers the signed-integer caveats). Memory is a PHP array of bytes, and loads/stores use `pack`/`unpack`.

## Tests

Pest. Feature tests are grouped as `tests/Feature/{StandardOpcode,ExtensionOpcode,SimdOpcode}/<type>Test.php`. The usual pattern is one `test()` per opcode, with a `->with(['module' => fn() => wat2module(<<<WAT ... WAT)])` dataset. `wat2module()` and `compileWat()` are global helpers in `tests/Pest.php`. Assert on `$ret[0]->getValue()`; results come back as an array.
