<?php declare(strict_types = 1);
namespace Slate\Sysv {

    use Closure;
    use Generator;
    use Slate\Data\Contract\IArrayBackwardConvertable;
    use Slate\Data\Contract\IArrayConvertable;
    use Slate\Data\Contract\IArrayForwardConvertable;
    use Slate\Facade\DB;
    use Slate\Facade\App;
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Entity;
    use Slate\Neat\Model;
    use Slate\Sql\SqlColumn;
    use Slate\Sysv\SysvSharedMemoryTable\SysvSharedMemoryTableQuery;
    use SysvSharedMemory;

        
    /**
     * A table that uses the SysvMemoryShare as its primary store,
     * which can be loaded and unloaded from a sql table. Its faster
     * than services like redis because there is no socket overhead.
     * But as a primary storage option, this class' purpose isnt that.
     * 
     * A good use case would be storage for auxiliary services, such 
     * as sessions.
     * 
     * TODO: add indexes to all columns - whether it be a key or not
     * TODO: add binary trees to where queries
     */
    class SysvSharedMemoryTable extends SysvSharedMemoryLinkedList {
        public const VAR_AUTO_INCREMENT  = 6;

        /**
         * Max number of rows in the table, calculates the size based on this.
         *
         * @var integer
         */
        protected int    $rows;

        /**
         * The entity the table will be storing models for.
         *
         * @var string
         */
        protected string $entity;

        /**
         * Index store to increase filter speeds.
         *
         * @var array
         */
        public array     $indexes;

        /**
         * Counters to count the number of unique values for a column if its not a key.
         * This increases the speed of the count function.
         *
         * @var array
         */
        public array     $counters;

        /**
         * A factory that creates entity models from rows.
         *
         * @var Closure|null
         */
        protected ?Closure $factory;

        public function __construct(int $key, int $permissions, int $rows, string $entity, ?Closure $factory = null) {
            $this->entity   = $entity;
            $this->rows     = $rows;

            $this->indexes  = [];
            $this->counters = [];
            $this->factory  = $factory;

            parent::__construct($key, 128, $permissions);
            
            $this->prep();
        }

        /**
         * Entity getter.
         *
         * @return string
         */
        public function getEntity():string {
            return $this->entity;
        }

        /**
         * This function takes place after the table has been acquired.
         * Here indexes and counters are registered as hashmaps.
         *
         * @return void
         */
        public function prep(): void {
            $columns = $this->entity::design()->getColumns();

            foreach(\Arr::values($columns) as $index => $attr) {
                $column = $attr->getColumn($this->entity);

                if(!$column->isKey()) {
                    $this->counters[$attr->parent->getName()] = 
                        new SysvSharedMemoryDictionary(
                            /** All of the counters are kept straight after the table */
                            $this->key + $index,
                            $column->getType()->getSize() * $this->rows,
                            $this->permissions,
                            linkedListKey: $this->key + $index + count($columns),
                            linkedListPermissions: $this->permissions
                        );
                }
                else {
                    $this->indexes[$attr->parent->getName()] =
                        new SysvSharedMemoryDictionary(
                            /** All of the key indexes are kept after the counters */
                            $this->key + $index + count($columns) * 2,
                            $column->getType()->getSize() * $this->rows,
                            $this->permissions,
                            $this->key + $index + count($columns) * 3,
                            linkedListPermissions: $this->permissions
                        );
                }
            }
            
            $this->acquireIndexes();
            $this->acquireCounters();
        }

        /**
         * Generate a new query for this table.
         *
         * @return SysvSharedMemoryTableQuery
         */
        public function query(): SysvSharedMemoryTableQuery {
            return (new SysvSharedMemoryTableQuery($this));
        }

        /**
         * @see SysvSharedMemory::reacquire()
         *
         * @return void
         */
        public function reacquire(): void {
            parent::reacquire();

            foreach($this->indexes as $index)
                $index->reacquire();

            foreach($this->counters as $counter)
                $counter->reacquire();
        }

        /**
         * @see SysvSharedMemory::acquire()
         *
         * @return void
         */
        public function acquire(): void {
            parent::acquire();
            
            $this->acquireIndexes();
            $this->acquireCounters();
        }

        /**
         * Acquires all indexes.
         *
         * @return void
         */
        protected function acquireIndexes(): void {
            foreach($this->indexes as $index)
                $index->acquire();
        }

        /**
         * Acquires all counters.
         *
         * @return void
         */
        protected function acquireCounters(): void {
            foreach($this->counters as $counter)
                $counter->acquire();
        }

        /**
         * Filter rows in the table by value.
         * At the moment no complex filters are allowed.
         *
         * @param string  $key Property name
         * @param mixed   $value Value to search for
         * @param integer $limit Same functionality as sql
         * @param integer $offset Same functionality as sql
         * @param boolean $strict Whether strict matching is used
         *
         * @return Generator
         * 
         * TODO: use JIT structure or array for complex searching
         */
        public function where(string $key, mixed $value, int $limit = 0, int $offset = 0, bool $strict = false): Generator {
            if(($attr = $this->entity::design()->getColumnProperty($key)) === null)
                throw new \Error("Unknown column '$key'.");

            if($attr->isUniqueKey() && is_scalar($value)) {
                $index = $this->indexes[$attr->parent->getName()];
                $offset = $index[$value];

                if($index->offsetExists($value)) {
                    yield [$offset, $this[$offset]];
                }
            }
            else {
                foreach(parent::where($attr->getColumnName(), $value, $limit, $offset, $strict) as $entry) {
                    yield $entry;
                }
            }
        }

        /**
         * Count the number of rows which match a value or closure filter.
         *
         * @param Closure|string|null $filter
         * @param string|integer|float|null $value
         *
         * @return integer
         */
        public function count(Closure|string $filter = null, string|int|float $value = null): int {
            if(is_string($filter)) {
                if(($attr = $this->entity::design()->getColumnProperty($filter)) !== null) {
                    $column = $attr->getColumn($this->entity);

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

        public function isFull(): bool {
            return ($this->pull(static::VAR_ROWS_COUNT) === $this->rows);
        }

        /**
         * Unload the rows into the source sql table.
         *
         * @return void
         * 
         * TODO: add to commandline tool
         */
        public function unload(): void {
            $conn = App::conn($this->entity::conn());

            $ref = $this->entity::ref()->toString();

            $insertQuery = DB::insert()->into($ref)->conflictMirror();

            $incrementalColumn = \Arr::first($this->entity::design()->getColumns(), function($attr) {
                $column = $attr->getColumn($this->entity);

                return $column->isIncremental();
            });

            $incrementalColumnMax = 1;

            foreach($this as $row) {
                if($incrementalColumn !== null) {
                    if(($value = $row[$incrementalColumn->getColumnName()]) > $incrementalColumnMax)
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

        /**
         * Load the rows from the source sql table.
         *
         * @return void
         * 
         * TODO: add to commandline tool
         */
        public function load(): void {
            $conn = $this->entity::conn();
            
            $rows =
                DB::select(
                    \Arr::map(
                        $this->entity::design()->getColumns(),
                        fn(Column $attr): string => $conn->wrap($attr->getColumnName())
                    )
                )
                ->from($conn->wrap($this->entity::SCHEMA, $this->entity::TABLE))
                ->using($conn);
            
            $rows = $rows->get();

            foreach($rows as $row) {
                parent::offsetPush(\Arr::values($row));
            }
        }

        public function toRow(int $pointer, Entity $model, bool $set = false): array {
            foreach($this->entity::design()->getColumns() as $index => $attribute) {
                $value  = $model->{$attribute->parent->getName()};
                $column = $attribute->getColumn($this->entity);
                
                if($column->isIncremental()) {
                    if($value === null) {
                        if($set)
                            throw new \Error(
                                \Str::format(
                                    "Incremental column {}::\${} cannot be null while settting rows.",
                                    $this->entity,
                                    $attribute->parent->getName()
                                )
                            );

                            $model->{$attribute->parent->getName()}= $this->preIncrement(static::VAR_AUTO_INCREMENT);
                    }
                    else if(!$set) {
                        if($column->isIncremental())
                            throw new \Error(
                                \Str::format(
                                    "Incremental column {}::\${} must be null when appending rows.",
                                    $this->entity,
                                    $attribute->parent->getName()
                                )
                            );
                    }
                }
                else if($column->isKey() && !$set) {
                    $index = $this->indexes[$attribute->getColumnName()];

                    if($index->offsetExists($value))
                        throw new \Error("Unique value for column '{$attribute->getColumnName()}' already exists.");

                    $index[$value] = $pointer;
                }
            }

            
            return \Arr::values($model->toSqlRow());
        }

        public function getFactory(): ?Closure {
            return $this->factory;
        }

        public function fromRow(array $row): Entity {
            $row = \Arr::key(
                $row, \Arr::map(
                    \Arr::values($this->entity::design()->getColumns()),
                    fn(Column $column) => $column->getColumnName()
                )
            );

            $entityClass = $this->entity;

            if($this->factory !== null) {
                $entityClass = ($this->factory)($row);

                if($entityClass === null)
                    throw new \Error("Entity factory didn't provide a class.");

                if(is_object($entityClass) ? !\Cls::isSubclassInstanceOf($entityClass, $this->entity) : false)
                    throw new \Error("Entity factory class $entityClass must inherit from the Entity provided in the constructor.");
            }

            $model = new $entityClass;
            $model->fromSqlRow($row);

            return $model;
        }

        public function offsetUnset($index): void {
            if($this->offsetExists($index) && !\Arr::isEmpty($this->indexes)) {
                $row = \Arr::key(parent::offsetGet($index), \Arr::map($this->entity::design()->getColumns(), fn($attr) => $attr->getColumnName()));
                
                if($row !== null) {
                    foreach($this->entity::design()->getColumns() as $attr) {
                        $value = $row[$attr->getColumnName()];
                        $column = $attr->getColumn($this->entity);
                        
                        if($column->isKey()) {
                            $this->indexes[$attr->parent->getName()]->offsetUnset($value);
                        }
                        else {
                            $this->counters[$attr->parent->getName()][$value] = $this->counters[$attr->parent->getName()][$value] - 1;
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

        public function offsetPush($model): void {
            if(!\Cls::isSubclassInstanceOf($model, $this->entity))
                throw new \Error("You can only push Entity Models derived from the Entity class passed in the constructor, to a SysvSharedMemoryTable.");

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

            $row = $this->toRow($nextFree - $this->pull(static::VAR_ROWS_START), $model);

            $this->put($nextFree, [$row, $head, -1]);
            $this->put(static::VAR_HEAD_POINTER, $nextFree);
            
            $this->postIncrement(static::VAR_ROWS_COUNT);

            foreach($this->entity::design()->getColumns() as $attr) {
                $column = $attr->getColumn($this->entity);

                if(!$column->isKey()) {
                    $value = $row[$attr->getColumnName()];

                    $counter = $this->counters[$attr->parent->getName()];
    
                    if(!$counter->offsetExists($value))
                        $counter[$value] = 0;
    
                    $counter[$value] = $counter[$value]+1;
                }
            }
        }

        public function initialise(): void {
            /**
             * Calculate the size by getting the max rows multiplied by the sum of the column storage sizes.
             */
            $size = (
                $this->rows * \Arr::sum(
                    \Arr::map(
                        $this->entity::design()->getAttrInstances(Column::class),
                        function($attribute) {
                            return $attribute->getColumn($this->entity)->getType()->getSize();
                        }
                    )
                )
            );

            $this->size = $size;

            $this->destroy();
            
            $this->resource = \shm_attach($this->key, $this->size, $this->permissions);
            $this->assertAcquisition();
            
            $this->put(static::VAR_INIT, true);

            $rowsStart = static::VAR_FREE_POINTER + 1;

            $this->put(static::VAR_ROWS_COUNT,    0);
            $this->put(static::VAR_ROWS_START,    $rowsStart);
            $this->put(static::VAR_HEAD_POINTER,  -1);
            $this->put(static::VAR_TAIL_POINTER,  -1);
            $this->put(static::VAR_FREE_POINTER,  $rowsStart);

            if(($incrementalColumnAttr = $this->entity::design()->getIncrementalColumn()) !== null) {
                $this->put(static::VAR_AUTO_INCREMENT, $incrementalColumnAttr->getColumn($this->entity)->getAutoIncrement());
            }
        }

        public function offsetAssign($index, $row): void {
            parent::offsetAssign($index, $this->toRow($index, $row, true));
        }

        /** @see Iterator::current() */
        public function current(): mixed {
            return $this->has($this->pointer) ? $this->fromRow($this->pull($this->pointer)[0]) : null;
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