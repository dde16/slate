<?php

namespace Slate\IO {

    use Closure;
    use Generator;
    use Slate\Data\IArrayBackwardConvertable;
    use Slate\Data\IArrayConvertable;
    use Slate\Data\IArrayForwardConvertable;
    use Slate\Facade\DB;
    use Slate\Mvc\App;
    use Slate\Neat\Attribute\Column;
    use Slate\Neat\Entity;
    use Slate\Neat\Model;
    use Slate\Sql\SqlColumn;
    use SysvSharedMemory;

        
    /**
     * A Memory Share which can be loaded and deposited from a sql table.
     */
    class SysvSharedMemoryTable extends SysvSharedMemoryLinkedList {
        public const OVERHEAD = .2;

        public const VAR_AUTO_INCREMENT  = 6;

        protected int    $rows;

        protected string $entity;
        
        protected string $repo;

        public array     $indexes;

        public array     $counters;

        protected ?Closure $factory;

        public function __construct(int $key, int $permissions, int $rows, string $repo, string $entity, ?Closure $factory = null) {
            $this->entity   = $entity;
            $this->rows     = $rows;

            $this->repo     = $repo;

            $this->indexes  = [];
            $this->counters = [];
            $this->factory  = $factory;

            parent::__construct($key, 128, $permissions);
            
            $this->prep();
        }

        public function prep(): void {
            $columns = $this->entity::design()->getColumns();

            foreach(\Arr::values($columns) as $index => $attr) {
                $column = $attr->getColumn();

                if($column->isKey()) {
                    $this->indexes[$attr->parent->getName()] =
                        new SysvSharedMemoryHashmap($this->key + count($columns) + $index, $column->getType()->getSize() * $this->rows, $this->permissions);
                }
                else {
                    $this->counters[$attr->parent->getName()] = 
                        new SysvSharedMemoryHashmap($this->key + $index, $column->getType()->getSize() * $this->rows, $this->permissions);
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

        public function where(string $key, $value, int $limit = 0, int $offset = 0, bool $strict = false): Generator {
            if(($attr = $this->entity::design()->getColumnProperty($key)) === null)
                throw new \Error("Unknown column '$key'.");

            $column = $attr->getColumn();

            if($column->isUniqueKey() && is_scalar($value)) {
                $index = $this->indexes[$attr->parent->getName()];
                $offset = $index[$value];

                if($index->offsetExists($value)) {
                    yield [$offset, $this[$offset]];
                }
            }
            else {
                foreach(parent::where($column->getName(), $value, $limit, $offset, $strict) as $entry) {
                    yield $entry;
                }
            }
        }

        public function count(Closure|string $filter = null, string|int|float $value = null): int {
            if(is_string($filter)) {


                if(($attr = $this->entity::design()->getColumnProperty($filter)) !== null) {
                    $column = $attr->getColumn();

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

        public function unload(): void {
            $conn = App::conn($this->entity::conn());

            $ref = $this->entity::ref()->toString();

            $insertQuery = DB::insert()->into($ref)->conflictMirror();

            $incrementalColumn = \Arr::first($this->entity::design()->getColumns(), function($atttr) {
                return $atttr->getColumn()->isIncremental();
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

        public function load(): void {
            $conn = $this->entity::conn();
            
            $rows = DB::select(\Arr::map($this->entity::design()->getColumns(), function($attr) use($conn) {
                return $conn->wrap($attr->getColumn()->getName());
            }))->from(
                $conn->wrap($this->entity::SCHEMA).".".$conn->wrap($this->entity::TABLE)
            )->using($conn);
            
            $rows = $rows->get();

            foreach($rows as $row) {
                parent::offsetPush(\Arr::values($row));
            }
        }

        public function toRow(int $pointer, Entity $model, bool $set = false): array {
            foreach($this->entity::design()->getColumns() as $index => $attribute) {
                $column = $attribute->getColumn();
                $value  = $model->{$attribute->parent->getName()};
                
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
                    $index = $this->indexes[$column->getName()];

                    if($index->offsetExists($value)) {
                        throw new \Error("Unique value already exists.");
                    }
                    else {
                        $index[$value] = $pointer;
                    }
                }
            }

            
            return \Arr::values($model->toSqlRow());
        }

        public function fromRow(array $row): Entity {
            $row = \Arr::key($row, \Arr::keys($this->entity::design()->getColumns()));

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
                        $column = $attr->getColumn();
                        $value = $row[$column->getName()];
                        
                        if($column->isKey()) {
                            $this->indexes[$attr->parent->gettName()]->offsetUnset($value);
                        }
                        else {
                            $this->counters[$attr->parent->gettName()][$value] = $this->counters[$attr->parent->gettName()][$value] - 1;
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
                $column = $attr->getColumn();
                
                if(!$column->isKey()) {
                    $value = $row[$column->getName()];

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
            $size = ($this->rows * \Arr::sum(\Arr::map(
                $this->entity::design()->getAttrInstances(Column::class),
                function($attribute) {
                    return $attribute->getColumn()->getType()->getSize();
                }
            )));

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

            if(($incrementalColumnAttr = \Arr::first($this->entity::design()->getColumns(), fn($attr) => $attr->getColumn()->isIncremental())) !== null) {
                $this->put(static::VAR_AUTO_INCREMENT, $incrementalColumnAttr->getColumn()->getAutoIncrement());
            }
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