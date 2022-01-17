<?php

namespace Slate\Sql {
    use Slate\Facade\DB;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlRowFormatClause;
    use Slate\Sql\Constraint\SqlCheckConstraint;
    use Slate\Sql\Constraint\SqlPrimaryKeyConstraint;
    use Slate\Sql\Constraint\SqlUniqueConstraint;
    use Slate\Sql\Expression\SqlColumnBlueprint;
    use Throwable;
    use Slate\Sql\Type\SqlType;

    final class SqlTable implements ISqlStorageMedium {
        protected SqlSchema $schema;
        protected string    $name;
        public array     $columns;

        protected ?int      $increment;

        use TSqlCollateClause;
        use TSqlRowFormatClause;

        public function __construct(SqlSchema $schema, string $name) {
            $this->schema = $schema;
            $this->name   = $name;
            $this->columns = [];
            $this->checks  = [];
            $this->increment = null;
        }

        public function load(): void {
            $this->columns = [];
            $this->checks  = [];

            $columns = $this->conn()->schematic($this->schema->name(), $this->name());

            foreach($columns as $columnInfo) {
                $column = $this->column($columnInfo["name"]);
                $column->fromArray($columnInfo);
            }
        }

        public function schema(): SqlSchema {
            return $this->schema;
        }

        public function conn(): SqlConnection {
            return $this->schema->conn();
        }

        public function getPrimaryKey(): ?SqlColumn {
            return null;
        }

        public function increments(string $name): SqlColumn {
            $column = $this->primary($name);
            $column->increments();

            return $column;
        }

        public function primary(string $name): SqlColumn {
            $column = $this->column($name);
            $column->primary();

            return $column;
        }

        public function column(string $name): ?SqlColumn {
            $column = &$this->columns[$name];

            if($column === null) {
                $column = new SqlColumn($this);
                $column->setName($name);
            }

            return $column;
        }

        public function has(string $name): bool {
            return \Arr::hasKey($this->columns, $name);
        }

        public function name(): string {
            return $this->name;
        }

        public function fullname(): string {
            return $this->conn()->wrap($this->schema->name(), $this->name);
        }

        public function exists(): bool {
            return DB::select()
                ->from("information_schema.TABLES")
                ->where("TABLE_SCHEMA", $this->schema->name())
                ->where("TABLE_NAME", $this->name)
                ->exists();
        }

        public function drop(): void {
            DB::drop()
                ->table($this->fullname())
                ->go();
        }

        public function commit(): void {
            if($this->exists()) {
                $preflight = DB::alter()->table($this->fullname());
                $alter = clone $preflight;

                $dbColumns =
                    DB::select([
                        "`column`"              => "C.COLUMN_NAME",
                        "`index_name`"          => "ST.INDEX_NAME",
                        "`index_type`"          => "ST.INDEX_TYPE"
                    ])
                    ->from("INFORMATION_SCHEMA.COLUMNS", as: "C")
                    ->leftJoin(
                        "INFORMATION_SCHEMA.STATISTICS",
                        function($join) {
                            return
                                $join
                                    ->on("C.COLUMN_KEY",         "MUL")
                                    ->on("C.TABLE_SCHEMA",       DB::raw("ST.TABLE_SCHEMA"))
                                    ->on("C.TABLE_NAME",         DB::raw("ST.TABLE_NAME"))
                                    ->on("C.COLUMN_NAME",        DB::raw("ST.COLUMN_NAME"))
                                ;
                        },
                        as: "ST"
                    )
                    ->where("C.TABLE_SCHEMA", "=", $this->schema->name())
                    ->where("C.TABLE_NAME", "=", $this->name())
                    ->using($this->conn())
                ;

                $dbConstraints = 
                    DB::select([
                        "`column`"              => "C.COLUMN_NAME",

                        "`name`"                => "TC.CONSTRAINT_NAME",
                        "`type`"                => "TC.CONSTRAINT_TYPE",

                        "`referring_schema`"    => "KCU.REFERENCED_TABLE_SCHEMA",
                        "`referring_table`"     => "KCU.REFERENCED_TABLE_NAME",
                        "`referring_column`"    => "KCU.REFERENCED_COLUMN_NAME",

                        "`match_option`"        => "RC.MATCH_OPTION",
                        "`on_update`"           => "RC.UPDATE_RULE",
                        "`on_delete`"           => "RC.DELETE_RULE",
                    ])
                    ->from("INFORMATION_SCHEMA.COLUMNS", as: "C")
                    ->innerJoin(
                        "INFORMATION_SCHEMA.KEY_COLUMN_USAGE",
                        function($join) {
                            return $join
                                ->on("C.TABLE_SCHEMA",      DB::raw("KCU.TABLE_SCHEMA"))
                                ->on("C.TABLE_NAME",        DB::raw("KCU.TABLE_NAME"))
                                ->on("C.COLUMN_NAME",       DB::raw("KCU.COLUMN_NAME"));
                        },
                        as: "KCU"
                    )
                    ->innerJoin(
                        "INFORMATION_SCHEMA.TABLE_CONSTRAINTS", 
                        function($join) {
                            return $join
                                ->on("TC.TABLE_SCHEMA", DB::raw("KCU.TABLE_SCHEMA"))
                                ->on("TC.TABLE_NAME",   DB::raw("KCU.TABLE_NAME"))
                                ->on("TC.CONSTRAINT_NAME",   DB::raw("KCU.CONSTRAINT_NAME"))
                            ;
                        },
                        as: "TC"
                    )
                    ->leftJoin(
                        "INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS",
                        function($join) {
                            return $join
                                ->on("TC.TABLE_SCHEMA",      DB::raw("RC.CONSTRAINT_SCHEMA"))
                                ->on("TC.TABLE_NAME",        DB::raw("RC.TABLE_NAME"))
                                ->on("TC.CONSTRAINT_NAME",   DB::raw("RC.CONSTRAINT_NAME"));
                        },
                        as: "RC"
                    )
                    ->where("C.TABLE_SCHEMA", "=", $this->schema->name())
                    ->where("C.TABLE_NAME", "=", $this->name())
                    ->using($this->conn())
                ;

                $dbColumns = \Arr::mapAssoc(
                    iterator_to_array($dbColumns->get()),
                    fn($index, $dbColumn): array => [$dbColumn["column"], $dbColumn]
                );

                $dbConstraints = \Arr::mapAssoc(
                    iterator_to_array($dbConstraints->get()),
                    fn($index, array $dbConstraint): array => [$dbConstraint["name"], $dbConstraint]
                );

                $dbColumnsNotLocal = array_diff(\Arr::keys($dbColumns), \Arr::keys($this->columns));

                if(!\Arr::isEmpty($dbColumnsNotLocal))
                    throw new \Error("Unspecified columns " . \Arr::list($dbColumnsNotLocal, ", ", "``") . ".");

                $localPrimaryColumn = $this->getPrimaryColumn();
                $dbPrimaryColumn = (
                    (($dbPrimaryConstraint = \Arr::first($dbConstraints, fn($dbConstraint) => $dbConstraint["type"] === "PRIMARY KEY")) !== null)
                        ? $dbColumns[$dbPrimaryConstraint["column"]]
                        : null
                );

                if($dbPrimaryColumn !== null && $localPrimaryColumn === null) {
                    $preflight->dropPrimaryKey();
                }
                else if($dbPrimaryColumn !== null && $localPrimaryColumn !== null) {
                    if($localPrimaryColumn->getName() !== $dbPrimaryColumn["column"]) {
                        $preflight->dropPrimaryKey();
                    }
                    else {
                        $localPrimaryColumn->buildIgnoreKeys = true;
                    }
                }
                else if($dbPrimaryColumn !== null && $localPrimaryColumn !== null) {
                    $localPrimaryColumn->buildIgnoreKeys = true;
                }

                foreach($this->columns as $localColumnName => $localColumn) {
                    (\Arr::hasKey($dbColumns, $localColumnName)
                        ? $alter->modify($localColumn)
                        : $alter->add($localColumn)
                    );

                    if(!\Arr::hasKey($dbColumns, $localColumnName)) {
                        if($localColumn->index)
                            $preflight->add($localColumn->index);
                    }
                    else {
                        $dbColumn = $dbColumns[$localColumnName];

                        $dbForeignConstraint = \Arr::first(
                            $dbConstraints,
                            fn($dbConstraint) => $dbConstraint["column"] === $dbColumn["name"]
                        );

                        $localColumnHasIndex = $localColumn->index !== null;
                        $dbColumnHasIndex = $dbColumn["index_name"] !== null;

                        if($localColumn->foreignKeyConstraint !== null) {
                            if(
                                (($dbForeignConstraint !== null)
                                    ? (
                                        $localColumn->foreignKeyConstraint->symbol !== $dbForeignConstraint["name"]
                                        || \Arr::any([
                                            \Str::lower($localColumn->foreignKeyConstraint->foreignSchema) !== \Str::lower($dbForeignConstraint["referring_schema"]),
                                            \Str::lower($localColumn->foreignKeyConstraint->foreignTable) !== \Str::lower($dbForeignConstraint["referring_table"]),
                                            \Str::lower($localColumn->foreignKeyConstraint->foreignColumn) !== \Str::lower($dbForeignConstraint["referring_column"]),
                                            \Str::lower($localColumn->foreignKeyConstraint->onUpdate) !== \Str::lower($dbForeignConstraint["on_update"]),
                                            \Str::lower($localColumn->foreignKeyConstraint->onDelete) !== \Str::lower($dbForeignConstraint["on_delete"])
                                        ])
                                    )
                                    : false
                                )
                            ) {
                                $preflight->dropForeignKey($dbForeignConstraint["name"]);
                            }

                            $alter->addConstraint($localColumn->foreignKeyConstraint);
                        }
                        else if($dbForeignConstraint !== null) {
                            $preflight->dropForeignKey($dbForeignConstraint["name"]);
                        }

                        if($localColumnHasIndex && !$dbColumnHasIndex) {
                            $preflight->add($localColumn->index);
                        }
                        else if(!$localColumnHasIndex && $dbColumnHasIndex) {
                            $preflight->dropIndex($dbColumn["index_name"]);
                        }
                        else if($localColumnHasIndex && $dbColumnHasIndex) {
                            if($dbColumn["index_name"] !== $localColumn->index->type) {
                                $preflight->dropIndex($dbColumn["index_name"]);
                                $alter->add($localColumn->index);
                            }
                        }
                    }
                }

                debug($preflight);
                debug($alter);

                // $preflight->go();
                // $alter->go();
            }
            else {
                $this->create();
            }
        }

        public function getPrimaryColumn(): ?SqlColumn{ 
            return \Arr::first($this->columns, fn(SqlColumn $column): bool => $column->isPrimaryKey());
        }

        public function lock(bool $readLocal = false, bool $writeLowPriority = false): void {
            DB::lock()->tableRead($readLocal)->tableWrite($writeLowPriority)->go();
        }

        public function lockRead(bool $local = false): void {
            DB::lock()->tableRead($local)->go();
        }

        public function lockWrite(bool $lowPriority = false): void {
            DB::lock()->tableWrite($lowPriority)->go();
        }

        public function locked(): bool {return true;}

        public function unlock(): void {}

        public function update(): void {}

        public function create(): void {
            if(!$this->schema->exists())
                $this->schema->create();

            $stmt = DB::create()->table($this->fullname())->using($this->conn());

            foreach($this->columns as $column) {
                if(\Cls::isSubclassInstanceOf($column, SqlColumn::class)) {
                    $stmt->column($column);

                    if($column->index !== null) {
                        $stmt->index($column->index);
                    }
                }
            }

            $stmt->go();
        }
    }
}

?>