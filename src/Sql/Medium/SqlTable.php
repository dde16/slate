<?php declare(strict_types = 1);

namespace Slate\Sql\Medium {

    use Error;
    use PDOException;
    use Slate\Facade\DB;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlRowFormatClause;
    use Slate\Sql\Condition\SqlCondition;
    use Slate\Sql\Constraint\SqlConstraintFactory;
    use Slate\Sql\Constraint\SqlForeignKeyConstraint;
    use Slate\Sql\Constraint\SqlPrimaryKeyConstraint;
    use Slate\Sql\Constraint\SqlUniqueConstraint;
    use Slate\Sql\Medium\Trait\TSqlTableTypes;
    use Slate\Sql\SqlColumn;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\SqlConstraint;

    final class SqlTable {
        protected SqlSchema $schema;
        protected string    $name;

        protected ?int      $increment;

        use TSqlTableTypes;
        use TSqlCollateClause;
        use TSqlRowFormatClause;
    
        public array $columns     = [];
        public array $indexes     = [];
        public array $constraints = [];

        public function __construct(SqlSchema $schema, string $name) {
            $this->schema = $schema;
            $this->name   = $name;
        }


        public function loadColumns(string ...$columns): void {
            $columnArrays = 
                DB::select([
                    "`position`"    => "ORDINAL_POSITION",
                    "`name`"        => "COLUMN_NAME",
                    "`nullable`"    => "IS_NULLABLE",
                    "`default`"     => "COLUMN_DEFAULT",
                    "`datatype`"    => "DATA_TYPE",
                    "`charLength`"  => "CHARACTER_MAXIMUM_LENGTH",
                    "`octetLength`" => "CHARACTER_OCTET_LENGTH",
                    "`precision`"   => "IF(ISNULL(NUMERIC_PRECISION), DATETIME_PRECISION, NUMERIC_PRECISION)",
                    "`scale`"       => "NUMERIC_SCALE",
                    "`charset`"     => "CHARACTER_SET_NAME",
                    "`collation`"   => "COLLATION_NAME",
                    "`extra`"       => "EXTRA"
                ])
                ->from("information_schema.COLUMNS")
                ->where("`TABLE_SCHEMA`", "=", $this->schema()->name())
                ->where("`TABLE_NAME`", "=", $this->name())
                ->where("`COLUMN_NAME`", "IN", $columns)
                ->get()
            ;

            foreach($columnArrays as $columnArray) {
                $column = $this->columns[$columnArray["name"]] = new SqlColumn($this, $columnArray["name"]);

                if(empty($columnArray["extra"]))
                    $columnArray["extra"] = null;

                $columnArray["nullable"] = $columnArray["nullable"] === "YES";

                $column->fromArray($columnArray);
            }
        }

        public function column(string $name): ?SqlColumn {
            if(!\Arr::hasKey($this->columns, $name)) {
                $this->loadColumns($name);
            }

            return $this->columns[$name];
        }


        public function getConstraint(string $name): ?SqlConstraint {
            $constraintInfo =
                $this->conn()
                    ->select([
                        "`TC.CONSTRAINT_NAME`"          => "TC.CONSTRAINT_NAME",
                        "`TC.CONSTRAINT_TYPE`"          => "TC.CONSTRAINT_TYPE",
                        "`KCU.COLUMN_NAME`"             => "KCU.COLUMN_NAME",
                        "`KCU.REFERENCED_TABLE_SCHEMA`" => "KCU.REFERENCED_TABLE_SCHEMA",
                        "`KCU.REFERENCED_TABLE_NAME`"   => "KCU.REFERENCED_TABLE_NAME",
                        "`KCU.REFERENCED_COLUMN_NAME`"  => "KCU.REFERENCED_COLUMN_NAME",
                        "`RC.CONSTRAINT_NAME`"          => "RC.CONSTRAINT_NAME",
                        "`RC.UPDATE_RULE`"              => "RC.UPDATE_RULE",
                        "`RC.DELETE_RULE`"              => "RC.DELETE_RULE"
                    ])
                    ->from("information_schema.TABLE_CONSTRAINTS as TC")
                    ->innerJoin("information_schema.KEY_COLUMN_USAGE as KCU", function(SqlCondition $join) {
                        return $join
                            ->where("KCU.TABLE_SCHEMA", "=", DB::raw("TC.TABLE_SCHEMA"))
                            ->where("KCU.TABLE_NAME", "=", DB::raw("TC.TABLE_NAME"))
                            ->where("KCU.CONSTRAINT_NAME", "=", DB::raw("TC.CONSTRAINT_NAME"));
                    })
                    ->leftJoin("information_schema.REFERENTIAL_CONSTRAINTS as RC", function(SqlCondition $join) {
                        return 
                            $join
                                ->where("RC.CONSTRAINT_SCHEMA", "=", DB::raw("KCU.TABLE_SCHEMA"))
                                ->where("RC.TABLE_NAME", "=", DB::raw("KCU.TABLE_NAME"))
                                ->where("RC.CONSTRAINT_NAME", "=", DB::raw("KCU.CONSTRAINT_NAME"))
                            ;
                    })
                    ->where("TC.TABLE_SCHEMA", $this->schema()->name())
                    ->where("TC.TABLE_NAME", $this->name())
                    ->where("TC.CONSTRAINT_NAME", $name)
                    ->nested([
                        "TC" => [
                            "TC.CONSTRAINT_NAME"
                        ],
                        "KCU" => [
                            "KCU.COLUMN_NAME"
                        ],
                        "RC" => [
                            "RC.CONSTRAINT_NAME"
                        ]
                    ])[$name]
            ;

            if($constraintInfo !== null) {

                $constraint = SqlConstraintFactory::create($constraintInfo["CONSTRAINT_TYPE"], [
                    $this, \Arr::keys($constraintInfo["children"]), $constraintInfo["CONSTRAINT_NAME"]
                ]);

                if($constraint instanceof SqlForeignKeyConstraint) {
                    $column = $constraintInfo["children"][$constraint->getColumn()];
                    $constraint->references(
                        $column["REFERENCED_TABLE_SCHEMA"],
                        $column["REFERENCED_TABLE_NAME"],
                        $column["REFERENCED_COLUMN_NAME"],
                    );
                    $constraint->onUpdate($column["children"][$name]["UPDATE_RULE"]);
                    $constraint->onDelete($column["children"][$name]["DELETE_RULE"]);
                }
            }
            else {
                $constraint = null;
            }

            return $constraint;
        }

        public function addUniqueKey(string ...$columns): SqlUniqueConstraint {
            $this->addConstraints[] = ($constraint = (new SqlUniqueConstraint($this, $columns)));

            return $constraint;
        }

        public function addPrimaryKey(string ...$columns): SqlPrimaryKeyConstraint {
            $this->addConstraints[] = ($constraint = (new SqlPrimaryKeyConstraint($this, $columns)));

            return $constraint;
        }

        public function addForeignKey(string $localColumn, string $foreignSchema, string $foreignTable, string $foreignColumn): SqlForeignKeyConstraint {
            $this->addConstraints[] = ($constraint = (new SqlForeignKeyConstraint($this, [$localColumn]))->references($foreignSchema, $foreignTable, $foreignColumn));

            return $constraint;
        }

        public function schema(): SqlSchema {
            return $this->schema;
        }

        public function conn(): SqlConnection {
            return $this->schema->conn();
        }

        public function ref(string $column): string {
            return $this->conn()->wrap($this->schema->name(), $this->name, $column);
        }

        public function hasConstraint(string $name): bool {
            return 
                $this->conn()
                    ->select([
                        DB::raw("1")
                    ])
                    ->from("information_schema.TABLE_CONSTRAINTS as TC")
                    ->where("TC.TABLE_SCHEMA", $this->schema()->name())
                    ->where("TC.TABLE_NAME", $this->name())
                    ->where("TC.CONSTRAINT_NAME", $name)
                    ->exists();
        }

        public function hasColumn(string $name): bool {
            return 
                $this->conn()
                    ->select([DB::raw("1")])
                    ->from("information_schema.COLUMNS")
                    ->where("TABLE_SCHEMA", $this->schema()->name())
                    ->where("TABLE_NAME", $this->name())
                    ->where("COLUMN_NAME", $name)
                    ->exists()
                ;
        }

        public function hasIndex(string $name): bool {
            return
                $this->conn()
                    ->select([DB::raw("1")])
                    ->from("information_schema.STATISTICS")
                    ->where("TABLE_SCHEMA", $this->schema()->name())
                    ->where("TABLE_NAME", $this->name())
                    ->where("INDEX_NAME", $name)
                    ->exists()
            ;
        }

        public function name(): string {
            return $this->name;
        }

        public function fullname(): string {
            return $this->conn()->wrap($this->schema->name(), $this->name);
        }

        public function exists(): bool {
            return $this->conn()
                ->select()
                ->from("information_schema.TABLES")
                ->where("TABLE_SCHEMA", $this->schema->name())
                ->where("TABLE_NAME", $this->name)
                ->exists();
        }

        public function drop(): void {
            $this->conn()
                ->dropTable($this->schema()->name(), $this->name)
                ->go();
        }

        public function lock(bool $readLocal = false, bool $writeLowPriority = false): void {
            $this->conn()->lock()->tableRead($this->name, $readLocal)->tableWrite($this->name, $writeLowPriority)->go();
        }

        public function lockRead(bool $local = false): void {
            $this->conn()->lock()->tableRead($this->name, $local)->go();
        }

        public function lockWrite(bool $lowPriority = false): void {
            $this->conn()->lock()->tableWrite($this->name, $lowPriority)->go();
        }

        public function locked(): bool {return true;}

        public function unlock(): void {}

        public function update(): void {}

        public function commit(): void  {
            foreach($this->commands as [$command, $target]) {
                switch($command) {
                    case "addColumn":
                    case "modifyColumn":
                        $this->conn()
                            ->alterTable($this->schema()->name(), $this->name())
                            ->add($target)
                            ->go();
                        break;
                }
            }
        }

        public function create(bool $recursive = true): void {
            if(!$this->schema->exists()) {
                if(!$recursive)
                    throw new PDOException("");

                $this->schema->create();
            }

            $this->conn()
                ->createTable($this->schema->name(), $this->name())
                ->go();
        }
    }
}

?>