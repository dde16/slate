<?php

namespace Slate\Neat {

    use Closure;
    use Slate\Facade\DB;
    use Slate\Facade\Security;
    use Slate\Facade\App;
    use Slate\Neat\Attribute\Column as ColumnAttribute;
    use Slate\Neat\Attribute\Scope as ScopeAttribute;

    use Slate\Sql\Type\ISqlTypeBackwardConvertable;
    use Slate\Sql\Type\ISqlTypeForwardConvertable;
    use Slate\Utility\TSnapshot;
    use Slate\Utility\ISnapshotExplicit;
    use Slate\Neat\Attribute\Alias;
    use Slate\Neat\Implementation\TScopeAttributeImplementation;
    use Slate\Neat\Implementation\TColumnAttributeImplementation;
    use Slate\Neat\EntityMarker;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\SqlSchema;
    use Slate\Sql\SqlTable;

class Entity extends Model implements ISnapshotExplicit {
        public const DESIGN  = EntityDesign::class;

        use TScopeAttributeImplementation;
        use TColumnAttributeImplementation;
        
        use TSnapshot;

        protected int   $marker = EntityMarker::DEFAULT;
        protected ?array $initial   = null;

        public const REF_SQL            = (1<<0);
        public const REF_RESOLVED       = (1<<1);
        public const REF_ITEM_WRAP      = (1<<2);
        public const REF_OUTER_WRAP     = (1<<3);
        public const REF_NO_WRAP        = (1<<4);

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
            return(static::query())->where(...\func_get_args());
        }

        public static function query(): EntityQuery { 
            return(new EntityQuery(static::class));
        }

        public static function plan(array $plan): EntityQuery {
            return(new EntityQuery(static::class, $plan));
        }

        public static function all(): array {
            return(static::query()->get());
        }

        public static function first(): object|null {
            $query = static::query();

            if(func_num_args() > 0)
                $query->where(...func_get_args());

            return $query->first();
        }

        public static function schema(): SqlSchema {
            return static::conn()->schema(static::SCHEMA);
        }

        public static function table(): SqlTable {
            return static::schema()->table(static::TABLE);
        }
        
        public static function ref(string $affix = null, int $flags = Entity::REF_SQL | Entity::REF_ITEM_WRAP): EntityReference {
            $columnName = $affix;
            $propertyName = $affix;
            
            return(new EntityReference(static::class, $propertyName, $columnName, $flags));
        }

        public function revert(): void {
            $this->fromSqlRow($this->initial);
            $this->snap(store: true);
        }

        public function fromSqlRow(array $array): void {
            if($this->initial === null)
                $this->initial = $array;

            foreach($array as $propertyName => $propertyValue) {
                if(($columnAttribute = static::design()->getAttrInstance(ColumnAttribute::class, $propertyName)) !== null) {
                    $sqlType = $columnAttribute->getColumn(static::class)->getType();

                    if($sqlType === null) {
                        throw new \Error(
                            \Str::format(
                                "Column {}::\${}({}) has doesn't have a type defined.",
                                static::class,
                                $columnAttribute->parent->getName(),
                                static::ref($columnAttribute->getColumnName())
                            )
                        );
                    }

                    if($columnAttribute->parent->hasType()) {
                        $phpType = $columnAttribute->parent->getType()->getName();

                        if(!\class_exists($phpType)) {
                            if(($nativeType = \Type::getByName($phpType)) === null) {
                                throw new \Error("Unknown type '" . $phpType . "'.");
                            }
                        }
                        else {
                            $nativeType = $phpType;
                        }
    
                        if(\Cls::hasInterface($sqlType::class, ISqlTypeBackwardConvertable::class) && $propertyValue !== null) {
                            try {
                                $propertyValue = $sqlType->fromSqlValue($propertyValue, $nativeType);
                            }
                            catch(\Throwable $throwable) {
                                throw new \Error(\Str::format(
                                    "Error while trying to convert {}: {}",
                                    static::ref($columnAttribute->getColumnName()),
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
                    $properties ?: \Arr::map(
                        $columns,
                        fn($column) => $column->parent->getName()
                    )
                ),
                function($propertyName, $propertyValue) use($columns) {
                    $propertyColumn = $columns[$propertyName];
                    $propertyColumnType = $propertyColumn->getColumn(static::class)->getType();

                    if($propertyValue !== null ? \Cls::hasInterface($propertyColumnType, ISqlTypeForwardConvertable::class) : false) {
                        $propertyValue = $propertyColumnType->toSqlValue($propertyValue);
                    }
                    else if(!$propertyColumn->isIncremental() && !$propertyColumn->isNullable()) {
                        throw new \Error(\Str::format(
                            "Property column '{}::\${}' cannot be null.",
                            static::class,
                            $propertyName
                        ));
                    }

                    $propertyValue = Security::sanitise($propertyValue, ["'"]);

                    return [$propertyColumn->getColumnName(), $propertyValue];
                }
            );
        }

        public function getPrimaryKey(): int|string|float {
            return $this->{static::design()->getPrimaryKey()->parent->getName()};
        }

        public static function scope(string $name, array $arguments): ?EntityStaticCarry {
            if(static::design()->getAttrInstance(ScopeAttribute::class, $name) !== null) {
                return static::{$name}(...$arguments);
            }

            return null;
        }

        #[Alias("commit")]
        public static function commitEntity(array|Closure $filter = null, bool $changed = true): int {
            $entities = static::class === Entity::class ? \Arr::filter(
                EntityDesign::$designs,
                function($design) {
                    return $design->isQueryable();
                }
            ) : [static::design()];

            if(is_array($filter)) {
                $objectIds = \Arr::map($filter, function($obj) {
                    return spl_object_id($obj);
                });

                $filter = function($class, $instance) use($objectIds) {
                    return \Arr::contains($objectIds, spl_object_id($instance));
                };
            }

            $committed = 0;

            foreach($entities as $entityDesign) {
                $entityClass = $entityDesign->getName();
                $entityModels = $entityDesign->getInstances();

                $primaryKeyColumn = $entityDesign->getPrimaryKey();
                $primaryKeyProperty = $primaryKeyColumn->parent->getName();

                $entityNonNullableColumns = \Arr::filter(
                    $entityDesign->getAttrInstances(
                        ColumnAttribute::class
                    ),
                    function($column) {
                        return $column->isPrimaryKey() || !$column->isNullable();
                    }
                );
                $entityAggregateColumns = $entityNonNullableColumns;

                $insertModels = [];
                $insertModelRows   = [];

                $updateModelRows   = [];

                foreach($entityModels as $entityModelId => $entityModel) {
                    $entityModelInsert = false;

                    $entityModelCommit = $entityModel->isMarkedWith(EntityMarker::UPSERT) || ($changed && $entityModel->hasChanged());

                    if($filter !== null)
                        $entityModelCommit = $entityModelCommit && $filter($entityClass, $entityModel);

                    if($entityModelCommit) {
                        $entityModelWhatsChanged = $entityModel->whatsChanged();

                        $entityModelColumns = \Arr::filter(
                            $entityDesign->getAttrInstances(
                                ColumnAttribute::class
                            ),
                            function($column) use($entityNonNullableColumns, $entityModelWhatsChanged) {
                                return
                                    \Arr::hasKey($entityNonNullableColumns, $column->parent->getName())
                                    || \Arr::contains($entityModelWhatsChanged, $column->parent->getName());
                            }
                        );

                        $entityAggregateColumns = (\Arr::merge($entityAggregateColumns, $entityModelColumns));

                        if($entityModel->{$primaryKeyProperty} === null) {
                            if($primaryKeyColumn->isIncremental() === false) {
                                throw new \Error(\Str::format(
                                    "Property column '{$primaryKeyProperty}' cannot be null."
                                ));
                            }

                            $entityModelInsert = true;
                        }

                        $entityModelRow = \Arr::mapAssoc(
                            $entityModel->toArray(
                                \Arr::values(
                                    \Arr::map(
                                        $entityModelColumns,
                                        function($column) {
                                            return $column->parent->getName();
                                        }
                                    )
                                )
                            ),
                            function($propertyName, $propertyValue) use($entityModelColumns) {
                                $propertyColumn = $entityModelColumns[$propertyName];
                                $propertyColumnType = $propertyColumn->getColumn(static::class)->getType();

                                if($propertyValue !== null ? \Cls::hasInterface($propertyColumnType, ISqlTypeForwardConvertable::class) : false) {
                                    $propertyValue = $propertyColumnType->toSqlValue($propertyValue);
                                }
                                else if(!$propertyColumn->isIncremental() && !$propertyColumn->isNullable()) {
                                    throw new \Error(\Str::format(
                                        "Property column '{}::\${}' cannot be null.",
                                        static::class,
                                        $propertyName
                                    ));
                                }

                                $propertyValue = Security::sanitise($propertyValue, ["'"]);

                                return [$propertyColumn->getColumnName(), $propertyValue];
                            }
                        );

                        $entityModel->snap(store: true);

                        if($entityModelInsert) {
                            $insertModels[] = $entityModel;
                            $insertModelRows[] = $entityModelRow;
                        }
                        else {
                            $updateModelRows[] = $entityModelRow;
                        }
                    }
                }

                $entityColumnsMap = \Arr::mapAssoc(
                    $entityAggregateColumns,
                    function($_, $column) {
                        return [$column->getColumnName(), $column->getColumnName()];
                    }
                );


                $conn = App::conn(\Cls::getConstant(static::class, "CONN"));

                $multiquery = [];

                if(!\Arr::isEmpty($insertModels)) {
                    $insertStatement  = DB::insert()->into(
                        $entityClass::ref()
                    );

                    $insertColumnsMap = \Arr::except($entityColumnsMap, $primaryKeyColumn->getColumnName());

                    $insertStatement->rows(
                        \Arr::map(
                            $insertModelRows,
                            function($insertModelRow) use($insertColumnsMap) {
                                return \Arr::rearrange($insertModelRow, $insertColumnsMap, function($key) {
                                    return DB::raw("`$key`");
                                });
                            }
                        )
                    );

                    $multiquery[] = $insertStatement->toString().";";

                    if($primaryKeyColumn->isIncremental()) {
                        $multiquery[] = 
                            ["SELECT LAST_INSERT_ID();", function($statement) use($insertModels, $primaryKeyColumn, $primaryKeyProperty) {
                                $lastId = $statement->fetchColumn(0);

                                $count = count($insertModels);

                                for($id = 0; $id < $count; $id++) {
                                    $insertModels[($count - 1) - $id]->{$primaryKeyProperty} = $lastId - $id;
                                }
                            }]
                        ;
                    }
                }

                if(!\Arr::isEmpty($updateModelRows)) {
                    $updateStatement  = DB::insert()->into(
                        $entityClass::ref()
                    )->conflictMirror();


                    $rows = \Arr::map(
                        $updateModelRows,
                        function($updateModelRow) use($entityColumnsMap, $primaryKeyProperty) {
                            return \Arr::rearrange($updateModelRow, $entityColumnsMap, function($key) {
                                return DB::raw("`$key`");
                            });
                        }
                    );

                    $updateStatement->rows($rows);

                    $multiquery[] = $updateStatement->toString().";";
                }


                if(!\Arr::isEmpty($multiquery)) {
                    $conn->beginTransaction();

                    try {
                        $conn->cbMultiquery($multiquery);
                    }
                    catch(\PDOException $e) {
                        if(!$conn->rollBack())
                            throw new \Error("Unable to rollback.");

                        throw $e;
                    }

                    $conn->commit();
                }
                    
                $committed += count($insertModelRows) + count($updateModelRows);
            }

            return $committed;
        }
        
        /**
         * Delete an instance by its primarykey or a filter.
         */
        #[Alias("delete")]
        public static function deleteStatic(string|int|Closure $filter): bool {
            if(!static::design()->isQueryable())
                throw new \Error("This Entity is not queryable, as it lacks a schema and or table.");

            $design = static::design();

            if(is_scalar($filter)) {
                $filter = function($condition) use($filter, $design) {
                    return $condition->where($design->getPrimaryKey()->getColumnName(), $filter);
                };
            }

            return DB::delete(static::ref())->where($filter)->go();
        }

        #[Alias("delete")]
        public function deleteInstance(): void {
            $design = static::design();

            static::deleteStatic(function($condition) use($design) {
                $primaryKeyColumn = $design->getPrimaryKey();

                return $condition->where(static::ref($primaryKeyColumn->getColumnName()), $this->getPrimaryKey());
            });

            $this->__destruct();
        }

        public function isMarkedWith(int $marker): bool {
            return $this->marker === $marker;
        }

        #[Alias("commit")]
        public function commitInstance(): int {
            return static::commitEntity([$this]);
        }

        public static function count(): int {
            return static::query()->count();
        }
    }
}

?>