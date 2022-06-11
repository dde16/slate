<?php declare(strict_types = 1);

namespace Slate\Neat {
    use Closure;
    use Error;
    use PDOStatement;
    use ReflectionClass;
    use RuntimeException;
    use SebastianBergmann\Environment\Runtime;
    use Slate\Facade\DB;
    use Slate\Facade\Security;
    use Slate\Facade\App;
    use Slate\Mvc\Env;
    use Slate\Neat\Attribute\Column as ColumnAttribute;
    use Slate\Neat\Attribute\Scope as ScopeAttribute;

    use Slate\Sql\Type\ISqlTypeBackwardConvertable;
    use Slate\Sql\Type\ISqlTypeForwardConvertable;
    use Slate\Utility\TSnapshot;
    use Slate\Utility\ISnapshotExplicit;
    use Slate\Neat\Attribute\Alias;
    use Slate\Neat\Attribute\OneToMany;
    use Slate\Neat\Attribute\OneToOne;
    use Slate\Neat\Attribute\PrimaryColumn;
    use Slate\Neat\Implementation\TScopeAttributeImplementation;
    use Slate\Neat\Implementation\TColumnAttributeImplementation;
    use Slate\Sql\Medium\SqlSchema;
    use Slate\Sql\Medium\SqlTable;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\SqlRaw;

    /**
     * @method void commit()
     */
    class Entity extends Model implements ISnapshotExplicit {
        public const DESIGN  = EntityDesign::class;

        use TColumnAttributeImplementation;
        
        use TSnapshot;

        private ?array $initial = null;

        public static function conn(bool $fallback = true): ?SqlConnection {
            return (($name = \Cls::getConstant(static::class, "CONN")) !== null)
                ? App::conn($name)
                : ($fallback
                    ? App::conn()
                    : null
                );
        }

        public function getSnapshotProperties(): array {
            $design = static::design();

            return \Arr::map(
                $design->getAttrInstances(ColumnAttribute::class),
                function($attr) {
                    return $attr->parent->getName();
                }
            );
        }

        public static function design(): EntityDesign {
            return EntityDesign::of(static::class);
        }

        public static function where(): EntityQuery {
            return (static::query())->where(...\func_get_args());
        }

        /**
         * @return EntityQuery
         */
        public static function query() {
            return(new EntityQuery(static::class));
        }

        public static function plan(array $plan): EntityQuery {
            return static::query()->plan($plan);
        }

        /**
         * Get all models for this entity.
         *
         * @return static[]
         */
        public static function all(): array {
            return(static::query()->get());
        }

        public static function first(): object|null {
            $query = static::query();

            if (func_num_args() > 0) {
                $query->where(...func_get_args());
            }

            return $query->first();
        }

        public static function schema(): SqlSchema {
            return static::conn()->schema(static::SCHEMA);
        }

        public static function table(): SqlTable {
            $schema = static::schema();
            
            return $schema->table(static::TABLE);
        }

        public function revert(): void {
            $this->fromSqlRow($this->initial);
            $this->snap(store: true);
        }

        public function fromSqlRow(array $array): void {
            if ($this->initial === null) {
                $this->initial = $array;
            }

            foreach ($array as $columnName => $propertyValue) {
                if (($columnAttribute = static::design()->getColumn($columnName)) !== null) {
                    $propertyName = $columnAttribute->parent->getName();

                    $sqlType = $columnAttribute->getColumn(static::class)->getType();

                    if ($sqlType === null) {
                        throw new \Error(
                            \Str::format(
                                "Column {}::\${}({}) has doesn't have a type defined.",
                                static::class,
                                $columnAttribute->parent->getName(),
                                static::conn()->wrap($columnAttribute->getColumnName())
                            )
                        );
                    }

                    if ($columnAttribute->parent->hasType()) {
                        $phpType = $columnAttribute->parent->getType()->getName();

                        if (!\class_exists($phpType)) {
                            if (($nativeType = \Type::getByName($phpType)) === null) {
                                throw new \Error("Unknown type '" . $phpType . "'.");
                            }
                        } else {
                            $nativeType = $phpType;
                        }
    
                        if ($sqlType instanceof ISqlTypeBackwardConvertable && $propertyValue !== null) {
                            try {
                                $propertyValue = $sqlType->fromSqlValue($propertyValue, $nativeType);
                            } catch (\Throwable $throwable) {
                                throw new \Error(\Str::format(
                                    "Error while trying to convert {}: {}",
                                    $columnAttribute->getColumnName(),
                                    $throwable->getMessage()
                                ), 0, $throwable);
                            }
                        }

                        unset($phpType);
                        unset($nativeType);
                    }


                    $this->__set($propertyName, $propertyValue);
                }
            }
        }

        public function toSqlRow(array $properties = null): array {
            $columns = static::design()->getColumns();

            return \Arr::mapAssoc(
                $this->toArray(
                    $properties ?? \Arr::map(
                        $columns,
                        fn($column) => $column->parent->getName()
                    )
                ),
                function($propertyName, $propertyValue) use ($columns) {
                    $propertyColumnAttribute = $columns[$propertyName];
                    $propertyColumn = $propertyColumnAttribute->getColumn(static::class);
                    $propertyColumnType = $propertyColumn->getType();

                    if ($propertyValue !== null ? \Cls::hasInterface($propertyColumnType, ISqlTypeForwardConvertable::class) : false) {
                        $propertyValue = $propertyColumnType->toSqlValue($propertyValue);
                    }
                    // else if(!$propertyColumn->isIncremental() && !$propertyColumn->isNullable()) {
                    //     throw new \Error(\Str::format(
                    //         "Property column '{}::\${}' cannot be null.",
                    //         static::class,
                    //         $propertyName
                    //     ));
                    // }

                    $propertyValue = Security::sanitise($propertyValue, ["'"]);

                    return [$propertyColumnAttribute->getColumnName(), $propertyValue];
                }
            );
        }

        public function getPrimaryKey(): int|string|float {
            $primaryKeys = array_values(static::design()->getPrimaryKeys());

            if (count($primaryKeys) > 1) {
                throw new RuntimeException("Unable to get the model primary key value as this entity has multiple primary keys.");
            }

            return $this->{$primaryKeys[0]->parent->getName()};
        }

        public static function scope(string $name, array $arguments): ?EntityStaticCarry {
            if (static::design()->getAttrInstance(ScopeAttribute::class, $name) !== null) {
                return static::{$name}(...$arguments);
            }

            return null;
        }

        public static function getChangeColumns(array $models) {
            $design = static::design();

            $nonNullableColumns = \Arr::filter(
                $design->getAttrInstances(ColumnAttribute::class),
                fn(ColumnAttribute $column): bool => $column instanceof PrimaryColumn || !($column->parent->getType()->allowsNull())
            );
            $aggregateColumns = $nonNullableColumns;

            foreach ($models as $model) {
                $modelChanges = $model->getChanges();

                $entityModelColumns = \Arr::filter(
                    $design->getAttrInstances(ColumnAttribute::class),
                    function($column) use ($nonNullableColumns, $modelChanges) {
                        return
                            \Arr::hasKey($nonNullableColumns, $column->parent->getName())
                            || \Arr::contains($modelChanges, $column->parent->getName());
                    }
                );

                $aggregateColumns = (\Arr::merge($aggregateColumns, $entityModelColumns));
            }

            return $aggregateColumns;
        }

        #[Alias("insert")]
        public static function insertModels(array $models): int {
            $insertCount = 0;

            if (!\Arr::isEmpty($models)) {
                $conn = static::conn();

                $insertColumns = static::getChangeColumns($models);

                $primaryKeyColumnAttributes = static::design()->getPrimaryKeys();
                $incrementalColumnAttribute = \Arr::first(
                    $primaryKeyColumnAttributes,
                    fn(PrimaryColumn $column): bool => $column->isIncremental()
                );

                $insertColumnsMap = \Arr::mapAssoc(
                    $insertColumns,
                    function($_, $column) {
                        return [$column->getColumnName(), $column->getColumnName()];
                    }
                );
                
                $insertColumnsProperties = \Arr::values(
                    \Arr::map(
                        $insertColumns,
                        fn($column): string => $column->parent->getName()
                    )
                );
    
                $insertRows    = [];
                $insertQueries = [];
    
                foreach ($models as $insertModel) {
                    $insertRows[] = \Arr::rearrange($insertModel->toSqlRow($insertColumnsProperties), $insertColumnsMap, function(string $key): SqlRaw {
                        return DB::raw(static::conn()->wrap($key));
                    });
                }

                $insertStatement = DB::insert()
                    ->into(static::table()->fullname())
                    ->rows($insertRows)
                ;

                $insertQueries[] = $insertStatement->toSql().";";

                if ($incrementalColumnAttribute !== null) {
                    $insertQueries[] = 
                        ["SELECT LAST_INSERT_ID();", function($statement) use ($models, $incrementalColumnAttribute) {
                            $lastId = $statement->fetchColumn(0);
                            $count = count($models);

                            for ($id = 0; $id < $count; $id++) {
                                $models[($count - 1) - $id]->{$incrementalColumnAttribute->parent->getName()} = $lastId - $id;
                            }
                        }]
                    ;
                }
                
                $insertQueries[] = [
                    "SELECT ROW_COUNT() as row_count;",
                    function(PDOStatement $statement) use (&$insertCount): void {
                        $insertCount = $statement->fetchColumn(0);
                    }
                ];

                $conn->transact(function() use ($conn, $insertQueries): void {
                    $conn->cbMultiquery($insertQueries);
                });
            }

            return $insertCount;
        }

        #[Alias("upsert")]
        #[Alias("commit")]
        #[Alias("update")]
        public static function upsertModels(array $models): int {
            $insertModels = [];
            $updateModels = [];

            $primaryKeyColumnAttributes = static::design()->getPrimaryKeys();

            foreach ($models as $model) {
                if (\Arr::any($model->toArray(\Arr::values(\Arr::map($primaryKeyColumnAttributes, fn(ColumnAttribute $column): string => $column->parent->getName()))), Closure::fromCallable('is_null'))) {
                    $insertModels[] = $model;
                } else {
                    $updateModels[] = $model;
                }
            }

            return static::updateModels($updateModels) + static::insertModels($insertModels);
        }

        public static function updateModels(array $models): int {
            $updateCount = 0;

            if (!\Arr::isEmpty($models)) {
                $conn = static::conn();
                $updateColumns = static::getChangeColumns($models);

                $updateColumnsMap = \Arr::mapAssoc(
                    $updateColumns,
                    function($_, $column) {
                        return [$column->getColumnName(), $column->getColumnName()];
                    }
                );

                $updateRows    = [];
                $updateQueries = [];

                $updateColumnsProperties = \Arr::values(
                    \Arr::map(
                        $updateColumns,
                        fn($column): string => $column->parent->getName()
                    )
                );

                foreach ($models as $updateModel) {
                    $updateRows[] = \Arr::rearrange($updateModel->toSqlRow($updateColumnsProperties), $updateColumnsMap, function(string $key): SqlRaw {
                        return DB::raw(static::conn()->wrap($key));
                    });
                }

                $updateQueries[] = 
                    $conn
                        ->insert()
                        ->into(static::table()->fullname())
                        ->rows($updateRows)
                        ->conflictMirror()
                        ->toSql()
                    .";"
                ;
                
                $updateQueries[] = [
                    "SELECT ROW_COUNT() as row_count;",
                    function(PDOStatement $statement) use (&$updateCount): void {
                        $updateCount = $statement->fetchColumn(0);
                    }
                ];

                $conn->transact(function() use ($conn, $updateQueries): void {
                    $conn->cbMultiquery($updateQueries);
                });

                foreach ($models as $model) {
                    $model->snap(store: true);
                }
            }

            return $updateCount;
        }
        
        /**
         * Delete an instance by its primarykey or a filter.
         */
        #[Alias("delete")]
        public static function deleteStatic(string|int|Closure $filter): bool {
            if (!static::design()->isQueryable()) {
                throw new \Error("This Entity is not queryable, as it lacks a schema and or table.");
            }

            $design = static::design();

            if (is_scalar($filter)) {
                $filter = function($condition) use ($filter, $design) {
                    return $condition->where($design->getPrimaryKey()->getColumnName(), $filter);
                };
            }

            return DB::delete(static::table()->fullname())->where($filter)->go();
        }

        #[Alias("delete")]
        public function deleteInstance(): void {
            $design = static::design();

            static::deleteStatic(function($condition) use ($design) {
                foreach ($design->getPrimaryKeys() as $primaryKeyColumn) {
                    $condition->where($primaryKeyColumn->getColumnName(), $this->{$primaryKeyColumn->parent->getName()});
                }

                return $condition;
            });

            $this->__destruct();
        }

        #[Alias("save")]
        #[Alias("upsert")]
        #[Alias("commit")]
        public function commitInstance(): bool {
            return static::updateModels([$this]) === 1 ? true : false;
        }

        #[Alias("insert")]
        public function insertInstance(): bool {
            return static::insertModels([$this]) === 1 ? true : false;
        }

        /**
         * Count the number of models for a given entity.
         *
         * @return integer
         */
        public static function count(): int {
            return static::query()->count();
        }

        public function hasMany(string $local, array $related): OneToMany {
            $reflection = new ReflectionClass(OneToMany::class);

            $instance = $reflection->newInstanceWithoutConstructor();
            $instance->setParent(static::design()->getMethod(callerof(__FUNCTION__)["function"])->construct());
            $instance->__construct($local, $related);
            
            return $instance;
        }

        // public function hasManyThrough(string $key, array $pivot, string $foreign, array $related) {
        //     $this->hasManyThrough(
        //         "uid",
        //         [DpdbMediaTag::class, "media_uid"],
        //         "person_uid",
        //         [DpdbPerson::class, "uid"],
        //     );
        // }

        // public function hasOne(): OneToOne {

        // }
    }
}

?>