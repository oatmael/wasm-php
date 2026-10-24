<?php

namespace Oatmael\WasmPhp;

use Exception;
use Oatmael\WasmPhp\Execution\Store;
use Oatmael\WasmPhp\Type\FuncRef;
use Oatmael\WasmPhp\Type\GlobalVariable;
use Oatmael\WasmPhp\Type\Import;
use Oatmael\WasmPhp\Type\Memory;
use Oatmael\WasmPhp\Type\Table;
use Oatmael\WasmPhp\Type\Tag;

class Module {
    public const MAX_CALL_STACK = 10_000;

    protected Store $store;

    public function __construct(
        protected int $version,
        array $types,
        array $rec_groups,
        array $imports,
        array $functions,
        array $codes,
        array $tables,
        array $memory,
        array $globals,
        array $tags,
        array $exports,
        array $elements,
        array $data,
        ?int $start,
    )
    {
        $this->store = new Store(
            types:            $types,
            rec_groups:       $rec_groups,
            imports:          $imports,
            functions:        $functions,
            codes:            $codes,
            table_types:      $tables,
            memory_types:     $memory,
            global_types:     $globals,
            tag_types:        $tags,
            exports:          $exports,
            element_segments: $elements,
            data_segments:    $data,
            start:            $start,
        );
    }

    // Provide an import: a PHP callable or another module's exported function for function imports, or a Table,
    // Memory, GlobalVariable or Tag (such as another module's export). Imports must be set before instantiation.
    public function setImport(string $module, string $field, mixed $value): self {
        $this->store->setImport($module, $field, $value);
        return $this;
    }

    /** @return Import[] */
    public function getImports(): array
    {
        return $this->store->imports;
    }

    // Instantiation initialises globals, tables and memories, and runs the start function. It happens automatically
    // the first time the module is used.
    public function instantiate(): self
    {
        $this->store->instantiate();
        return $this;
    }

    public function getExport(string $name): FuncRef|Table|Memory|GlobalVariable|Tag
    {
        $this->instantiate();
        return $this->store->getExport($name) ?? throw new Exception("No export named '$name'");
    }

    public function getMemory(int $index = 0): Memory
    {
        $this->instantiate();
        return $this->store->memory[$index] ?? throw new Exception('Memory index out of bounds');
    }

    public function execute(string $name, array $args): ?array
    {
        $this->instantiate();

        $function = $this->store->getExport($name);
        if (!($function instanceof FuncRef)) {
            throw new Exception("No exported function named '$name'");
        }

        $params = $function->type->params;
        $args = array_values($args);
        $bad_signature = count($params) !== count($args) ||
            array_any($args, fn ($arg, int $i) => !$this->store->valueMatches($arg, $params[$i]));

        if ($bad_signature) {
            $provided = array_map(get_debug_type(...), $args);
            throw new Exception('Bad export call - expected [' . implode(', ', array_map(static fn ($param) => $param->label(), $params)) . '], got [' . implode(', ', $provided) . ']');
        }

        $results = $this->store->invoke($function, $args);

        return $function->type->results ? $results : null;
    }
}
