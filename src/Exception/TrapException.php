<?php

namespace Oatmael\WasmPhp\Exception;

// A trap aborts execution. Messages start with the wording used by the spec's reference interpreter.
class TrapException extends WasmException {}
