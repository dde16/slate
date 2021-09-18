<?php

namespace Slate\IO {

    use Closure;
    use Generator;
    use Slate\Data\IArrayBackwardConvertable;
    use Slate\Data\IArrayConvertable;
    use Slate\Data\IArrayForwardConvertable;
    use Slate\Facade\DB;
    use Slate\Mvc\App;
    use Slate\Neat\Model;
    use Slate\Sql\SqlColumn;
    use SysvSharedMemory;

        
    /**
     * A Memory Share which can be loaded and deposited from a sql table.
     */
    class SysvSharedMemoryTable extends SysvSharedMemoryLinkedList {
        public const OVERHEAD = .2;

        public const VAR_AUTO_INCREMENT  = 6;
        public const VAR_COLUMNS_COUNT   = 7;
        public const VAR_COLUMNS_START   = 8;

        protected array  $columns;
        protected int    $rows;

        protected string $conn;
        protected string $schema;
        protected string $table;
        
        protected string $repo;

        public array     $indexes;

        public array     $counters;

        protected ?Closure $factory;

        public function __construct(int $key, int $permissions, int $rows, string $conn, string $repo, array $source, ?Closure $factory = null) {
            $this->conn  = $conn;
            $this->rows  = $rows;
            $this->conn  = $conn;

            $this->repo = $repo;

            $this->indexes = [];
            $this->counters = [];
            $this->columns = [];
            $this->factory = $factory;

            list($this->schema, $this->table) = $source;

            parent::__construct($key, 128, $permissions);
            
            $this->prep();
        }

        public function where(string $key, $value, int $limit = 0, int $offset = 0, bool $strict = false): Generator {
            if(($column = \Arr::first($this->columns, fn($column) => $column->getName() === $key)) === null)
                throw new \Error("Unknown column '$key'.");

            if($column->isUniqueKey() && is_scalar($value)) {
                $index = $this->indexes[$column->getName()];
                $offset = $index[$value];

                if($index->offsetExists($value)) {
                    yield [$offset, $this[$offset]];
                }
            }
            else {
                foreach(parent::where($key, $value, $limit, $offset, $strict) as $entry) {
                    yield $entry;
                }
            }
        }

        public function count(Closure|string $filter = null, string|int|float $value = null): int {
            if(is_string($filter)) {


                if(($column = $this->columns[$filter]) !== null) {
                    return
                        ($column->isKey())
                            ? ($this->indexes[$filter]->offsetExists($value) ? 1 : 0)
                            : ($this->counters[$filter][$value] ?: 0);
                }
                else {
                    throw new \Error("Unknown count column filter '{$filter}'.");
                }
            }

            return parent::count($filter);
        }

        protected function prep(): void {
            if(\Arr::isEmpty($this->columns)) {
                $columnsStart = static::VAR_COLUMNS_START;
                $columnsEnd   = $columnsStart + $this->pull(static::VAR_COLUMNS_COUNT);

                for($columnPointer = $columnsStart; $columnPointer < $columnsEnd; $columnPointer++) {
                    $column = unserialize($this->pull($columnPointer));

                    $columnIndex = $columnPointer - $columnsStart;

                    $this->columns[$column->getName()] = $column;

                    if($column->isKey()) {
                        $this->indexes[$column->getName()] =
                            new SysvSharedMemoryHashmap($this->key + count($this->columns) + $columnIndex, $column->getType()->getSize() * $this->rows, $this->permissions);
                    }
                    else {
                        $this->counters[$column->getName()] = 
                            new SysvSharedMemoryHashmap($this->key + $columnIndex, $column->getType()->getSize() * $this->rows, $this->permissions);
                    }
                }
            }
            
            $this->acquireIndexes();
            $this->acquireCounters();
        }

        public function reacquire(): void {
            parent::reacquire();

            foreach($this->indexes as $index)
                $index->reacquire();

            foreach($this->counters as $counter)
                $counter->reacquire();
        }

        public function acquire(): void {
            parent::acquire();
            
            $this->acquireIndexes();
            $this->acquireCounters();
        }

        protected function acquireIndexes(): void {
            foreach($this->indexes as $index)
                $index->acquire();
        }

        protected function acquireCounters(): void {
            foreach($this->counters as $counter)
                $counter->acquire();
        }

        public function toRow(int $pointer, array|IArrayConvertable $model, bool $set = false): array {
            $row = [];

            foreach($this->columns as $index => $column) {
                $columnScalarType = $column->getType()->getScalarType();

                if(is_object($model))
                    $model = $model->toArray();

                $value = $model[$column->getName()];

                if($value === null) {
                    
                    if($column->isIncremental())
                        $value = $this->preIncrement(static::VAR_AUTO_INCREMENT);

                    else if(!$column->isNullable())
                        throw new \Error(\Str::format(
                            "Column '{}' is not nullable.",
                            $column->getName()
                        ));
                }
                else if(!$set) {
                    if($column->isIncremental())
                        throw new \Error("Incremental column `{$column->getName()}` cannot be specified.");

                    if(!$columnScalarType::validate($value)) 
                        throw new \Error(\Str::format(
                            "Row value '{}' for column '{}' failed to validate as {}.",
                            $value,
                            $column->getName(),
                            $columnScalarType::NAMES[0]
                        ));

                    if($columnScalarType === \Str::class) {
                        $columnTypeSize = $column->getType()->getSize();

                        if(mb_strlen($value, "utf-8") > $columnTypeSize)
                            $value = substr($value, 0, $columnTypeSize);
                    }
                }

                if($column->isKey()) {
                    if(!$set) {
                        $index = $this->indexes[$column->getName()];
    
                        if($index->offsetExists($value)) {
                            throw new \Error("Unique value already exists.");
                        }
                        else {
                            $index[$value] = $pointer;
                        }
                    }
                }

                $row[$column->getName()] = $value;
            }

            return \Arr::values($row);
        }

        public function fromRow(array $row): array|IArrayConvertable {
            $row = \Arr::key($row, \Arr::keys($this->columns));

            if($this->factory !== null) {
                $modelClass = ($this->factory)($row);

                if(!\Cls::implements($modelClass, IArrayConvertable::class))
                    throw new \Error("Model class $modelClass from the factory must be array convertable.");
                
                $model = new $modelClass();
                $model->fromArray($row);
                
                return $model;
            }

            return $row;
        }

        public function isFull(): bool {
            return ($this->pull(static::VAR_ROWS_COUNT) === $this->rows);
        }

        public function load(Closure $filter = null): void {
            $conn = App::conn($this->conn);
            
            $rows = DB::select(\Arr::map($this->columns, function($column) use($conn) {
                return $conn->wrap($column->getName());
            }))->from(
                $conn->wrap($this->schema).".".$conn->wrap($this->table)
            )->using($this->conn);

            if($filter !== null)
                $rows->where($filter);
            
            $rows = $rows->get();

            foreach($rows as $row) {
                parent::offsetPush(\Arr::values($row));
            }
        }

        public function unload(): void {
            $conn = App::conn($this->conn);

            $ref = $conn->wrap($this->schema).".".$conn->wrap($this->table);

            $insertQuery = DB::insert()->into($ref)->conflictMirror();

            $incrementalColumn = \Arr::first($this->columns, function($column) {
                return $column->isIncremental();
            });

            $incrementalColumnMax = 1;

            foreach($this as $row) {
                if($incrementalColumn !== null) {
                    if(($value = $row[$incrementalColumn->getName()]) > $incrementalColumnMax)
                        $incrementalColumnMax = $value;
                }

                $insertQuery->row($row);
            }

            $insertQuery = $insertQuery->toString().";";

            if(!$conn->beginTransaction())
                throw new \Error("Unable to start transaction.");

            try {
                $conn->exec(\Str::format("DELETE FROM {};", $ref)."\n".$insertQuery);
            }
            catch(\Throwable $throwable) {
                $conn->rollBack();

                throw new \Error("Error while unloading Memory Table: " . $throwable->getMessage());
            }

            if($incrementalColumn !== null)
                $conn->exec(\Str::format("ALTER TABLE {} AUTO_INCREMENT = {}", $ref, $incrementalColumnMax+1));

        }

        public function offsetPush($row): void {
            if($this->isFull())
                throw new \Error("The table is full.");

            $head = $this->pull(static::VAR_HEAD_POINTER);
            $tail = $this->pull(static::VAR_TAIL_POINTER);

            $nextFree = $this->nextFree();

            if($head !== -1) {
                $this->modify($head, function($row) use($nextFree) {
                    $row[2] = $nextFree;

                    return $row;
                });
            }
            
            if($tail === -1) {
                $this->put(static::VAR_TAIL_POINTER, $nextFree);
            }

            $row = $this->toRow($nextFree - $this->pull(static::VAR_ROWS_START), $row);

            $this->put($nextFree, [$row, $head, -1]);
            $this->put(static::VAR_HEAD_POINTER, $nextFree);
            
            $this->postIncrement(static::VAR_ROWS_COUNT);

            foreach($this->columns as $column) {
                if(!$column->isKey()) {
                    $value = $row[$column->getName()];

                    $counter = $this->counters[$column->getName()];
    
                    if(!$counter->offsetExists($value))
                        $counter[$value] = 0;
    
                    $counter[$value] = $counter[$value]+1;
                }
            }
        }

        public function offsetUnset($index): void {
            if($this->offsetExists($index) && !\Arr::isEmpty($this->indexes)) {
                $row = \Arr::key(
                    parent::offsetGet($index),
                    \Arr::keys($this->columns)
                );
                
                if($row !== null) {
                    foreach($this->columns as $column) {
                        $columnName = $column->getName();
                        $rowValue = $row[$columnName];
                        
                        if($column->isKey()) {
                            $this->indexes[$columnName]->offsetUnset($rowValue);
                        }
                        else {
                            $this->counters[$columnName][$rowValue] = $this->counters[$columnName][$rowValue] - 1;
                        }
                    }
                }
            }

            parent::offsetUnset($index);
        }

        public function offsetGet($index): mixed {
            return (($row = parent::offsetGet($index)) !== false && $row !== null) ? $this->fromRow($row) : null;
        }

        public function destroy(): bool {
            $stat = parent::destroy();

            foreach($this->indexes as $index)
                $index->destroy();

            foreach($this->counters as $counter)
                $counter->destroy();
            
            return $stat;
        }

        public function initialise(): void {
            $conn = App::conn($this->conn);

            $key = "{$this->schema}.{$this->table}";

            if(App::repo($this->repo)->has($key)) {
                $columns = App::repo($this->repo)->pull($key);
            }
            else {
                App::repo($this->repo)->put(
                    $key, ($columns = $conn->schematic($this->schema, $this->table))
                );
            }
            
            $this->columns = \Arr::map(
                $columns,
                function($column) use($conn) {
                    $inst = new SqlColumn($conn::PREFIX);
                    $inst->fromArray($column);

                    return $inst;
                }
            );


            $size = ($this->rows * \Arr::sum(\Arr::map(
                $this->columns,
                function($column) {
                    return $column->getType()->getSize();
                }
            )));

            foreach($this->columns as $offset => $column) {
                $size += strlen(serialize($column));
            }

            $this->size = $size;

            $this->destroy();
            
            $this->resource = \shm_attach($this->key, $this->size, $this->permissions);
            $this->assertAcquisition();
            
            $this->put(static::VAR_INIT, true);

            $columnsCount = count($this->columns);
            $columnsStart = static::VAR_COLUMNS_START;
            $columnsEnd   = $columnsStart + $columnsCount;
            $rowsStart    = $columnsEnd;

            $this->put(static::VAR_COLUMNS_COUNT, $columnsCount);
            $this->put(static::VAR_COLUMNS_START, $columnsStart);
            $this->put(static::VAR_ROWS_COUNT,    0);
            $this->put(static::VAR_ROWS_START,    $rowsStart);
            $this->put(static::VAR_HEAD_POINTER,  -1);
            $this->put(static::VAR_TAIL_POINTER,  -1);
            $this->put(static::VAR_FREE_POINTER,  $rowsStart);

            $index = 0;

            foreach($this->columns as $name => $column) {
                if($column->isIncremental())
                    $this->put(static::VAR_AUTO_INCREMENT, $column->getAutoIncrement());

                $this->put($columnsStart + $index, serialize($column));

                $index += 1;
            }

        }

        public function getColumns(): array {
            return $this->columns;
        }
        
        public function offsetAssign($index, $row): void {
            parent::offsetAssign($index, $this->toRow($index, $row, true));
        }

        /** @see Iterator::current() */
        public function current(): mixed {
            return $this->has($this->position) ? $this->fromRow($this->pull($this->position)[0]) : null;
        }

        public function setAutoIncrement(int $value): void {
            $this->put($this->pull(static::VAR_AUTO_INCREMENT, 0), $value);
        }

        public function getAutoIncrement(): int {
            return $this->pull(static::VAR_AUTO_INCREMENT, 0);
        }

        public function truncate(): void {
            $this->destroy();
            
            parent::__construct($this->key, 128, $this->permissions);

            $this->prep();
        }
    }
}

?>