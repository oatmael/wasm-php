<?php

namespace Oatmael\WasmPhp\Import;

use Brick\Math\BigInteger;
use Closure;
use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\F32;
use Oatmael\WasmPhp\Type\F64;
use Oatmael\WasmPhp\Type\Func;
use Oatmael\WasmPhp\Type\I32;
use Oatmael\WasmPhp\Type\I64;
use Oatmael\WasmPhp\Type\Memory;
use Oatmael\WasmPhp\Type\NullRef;
use Oatmael\WasmPhp\Type\RefType;
use Oatmael\WasmPhp\Type\ValueInterface;
use Oatmael\WasmPhp\Util\ValueType;
use ReflectionAttribute;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;

// A PHP callable bound to an imported function's signature. The callable's parameters are matched against the
// wasm parameters once, when the import is registered, and values are converted between the two on every call.
class HostFunction
{
    // Each binding converts a slice of the wasm arguments into one PHP argument: [wasm offset, arity, converter]
    protected array $bindings = [];

    public function __construct(
        protected Closure $callable,
        protected Func $type,
        protected string $name,
    )
    {
        $offset = 0;
        foreach ((new ReflectionFunction($callable))->getParameters() as $param) {
            [$arity, $convert] = $this->bindParameter($param, array_slice($type->params, $offset));
            $this->bindings[] = [$offset, $arity, $convert];
            $offset += $arity;
        }

        if ($offset !== count($type->params)) {
            throw $this->signatureError('the callable only accepts ' . $offset . ' of its ' . count($type->params) . ' arguments');
        }
    }

    // Call with wasm arguments, returning the wasm results
    public function call(array $args, Store $store): array
    {
        $php_args = array_map(
            static fn (array $binding) => $binding[2](array_slice($args, $binding[0], $binding[1]), $store),
            $this->bindings,
        );

        return $this->toResults(($this->callable)(...$php_args));
    }

    protected function bindParameter(ReflectionParameter $param, array $wasm_params): array
    {
        $type = $param->getType();
        if ($param->isVariadic() || ($type !== null && !($type instanceof ReflectionNamedType))) {
            throw $this->parameterError($param, 'variadic, union and intersection types are not supported');
        }

        return match ($type?->getName() ?? 'mixed') {
            Memory::class => [0, static fn (array $args, Store $store) => $store->memory[0] ?? throw new Exception('Module has no memory')],
            Store::class  => [0, static fn (array $args, Store $store) => $store],
            'string'      => $this->bindString($param, $wasm_params),
            default       => [1, $this->bindValue($param, $type?->getName() ?? 'mixed', $wasm_params[0] ?? null)],
        };
    }

    protected function bindValue(ReflectionParameter $param, string $type_name, ValueType|RefType|null $wasm_type): Closure
    {
        if (!$wasm_type) {
            throw $this->parameterError($param, 'there are no wasm arguments left to bind it to');
        }

        // References are passed as they are: NullRef, FuncRef, ExternRef, and so on
        if ($wasm_type instanceof RefType) {
            if ($type_name !== 'mixed' && !is_a($type_name, ValueInterface::class, true)) {
                throw $this->parameterError($param, 'it cannot receive a ' . $wasm_type->label() . ' - references are passed as value objects');
            }

            return static fn (array $args) => $args[0];
        }

        $unsigned = (bool) $param->getAttributes(Unsigned::class);
        $unsigned_supported = ($type_name === 'int' && $wasm_type === ValueType::I32) || ($type_name === BigInteger::class && $wasm_type === ValueType::I64);
        if ($unsigned && !$unsigned_supported) {
            throw $this->parameterError($param, '#[Unsigned] is only supported on int parameters bound to an i32, or BigInteger parameters bound to an i64');
        }

        $convert = match (true) {
            $type_name === 'mixed', $type_name === ValueInterface::class,
            $type_name === $wasm_type->valueClass()                       => static fn (ValueInterface $value) => $value,
            $type_name === 'int' && $wasm_type === ValueType::I32         => $unsigned ?
                static fn (I32 $value) => $value->getValue() & 0xFFFFFFFF :
                static fn (I32 $value) => $value->getValue(),
            $type_name === BigInteger::class && $wasm_type === ValueType::I64 => static fn (I64 $value) => $value->toBigInteger($unsigned),
            $type_name === 'int' && $wasm_type === ValueType::I64,
            $type_name === 'float' && in_array($wasm_type, [ValueType::F32, ValueType::F64], true)
                                                                         => static fn (ValueInterface $value) => $value->getValue(),
            $type_name === 'bool' && $wasm_type === ValueType::I32        => static fn (I32 $value) => $value->getValue() !== 0,
            default => throw $this->parameterError($param, 'it cannot receive an ' . $wasm_type->label()),
        };

        return static fn (array $args) => $convert($args[0]);
    }

    protected function bindString(ReflectionParameter $param, array $wasm_params): array
    {
        $decoder = self::attribute($param, StringDecoder::class) ?? new PointerLength();
        $encoding = self::attribute($param, Encoding::class) ?? new Encoding();

        $arity = $decoder->arity();
        $string_params = array_slice($wasm_params, 0, $arity);
        if (count($string_params) !== $arity || array_any($string_params, static fn (ValueType|RefType $type) => $type !== ValueType::I32)) {
            throw $this->parameterError($param, $decoder::class . " strings take $arity i32 arguments");
        }

        return [$arity, static function (array $args, Store $store) use ($decoder, $encoding) {
            $memory = $store->memory[0] ?? throw new Exception('Module has no memory to read strings from');
            $pointers = array_map(static fn (I32 $value) => $value->getValue() & 0xFFFFFFFF, $args);

            return $encoding->decode($decoder->read($memory, $pointers, $encoding));
        }];
    }

    protected function toResults(mixed $ret): array
    {
        $results = $this->type->results;
        $values = match (count($results)) {
            // Return values from void imports are ignored, so arrow functions can be used freely
            0       => [],
            1       => [$ret],
            default => is_array($ret) ? array_values($ret) : [$ret],
        };

        if (count($values) !== count($results)) {
            throw new Exception("Import {$this->name} returned " . count($values) . ' values, expected ' . count($results));
        }

        return array_map($this->toValue(...), $values, $results);
    }

    protected function toValue(mixed $value, ValueType|RefType $type): ValueInterface
    {
        if ($type instanceof RefType) {
            return match (true) {
                $value === null && $type->nullable => new NullRef(),
                $value instanceof ValueInterface   => $value,
                default => throw new Exception("Import {$this->name} returned " . get_debug_type($value) . ', expected ' . $type->label()),
            };
        }

        return match (true) {
            $value instanceof ValueInterface && $value::class === $type->valueClass() => $value,
            is_bool($value) && $type === ValueType::I32                         => new I32((int) $value),
            is_int($value) && $type === ValueType::I32                          => new I32($value),
            is_int($value) && $type === ValueType::I64                          => new I64($value),
            $value instanceof BigInteger && $type === ValueType::I64            => I64::fromBigInteger($value),
            (is_int($value) || is_float($value)) && $type === ValueType::F32    => new F32((float) $value),
            (is_int($value) || is_float($value)) && $type === ValueType::F64    => new F64((float) $value),
            default => throw new Exception("Import {$this->name} returned " . get_debug_type($value) . ', expected ' . $type->label()),
        };
    }

    protected static function attribute(ReflectionParameter $param, string $class): ?object
    {
        return ($param->getAttributes($class, ReflectionAttribute::IS_INSTANCEOF)[0] ?? null)?->newInstance();
    }

    protected function parameterError(ReflectionParameter $param, string $reason): Exception
    {
        return $this->signatureError("parameter \${$param->getName()} can't be bound - $reason");
    }

    protected function signatureError(string $reason): Exception
    {
        $types = static fn (array $types) => implode(', ', array_map(static fn (ValueType|RefType $type) => $type->label(), $types));

        return new Exception("Import {$this->name} has signature ({$types($this->type->params)}) -> ({$types($this->type->results)}), but $reason");
    }
}
