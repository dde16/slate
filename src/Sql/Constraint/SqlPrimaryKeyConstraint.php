<?php declare(strict_types = 1);

namespace Slate\Sql\Constraint {

    use Slate\Facade\DB;
    use Slate\Sql\SqlConstruct;

    class SqlPrimaryKeyConstraint extends SqlMultiColumnConstraint {
        public const SYMBOL_SHORTHAND = "PK";

        public function buildSql(): array {
            return [
                "CONSTRAINT",
                $this->getSymbol(),
                "PRIMARY KEY",
                \Arr::list(
                    \Arr::map(
                        $this->columns, fn(string $column): string => $this->table->conn()->wrap($column)
                    ),
                    ", ",
                    "",
                    "()"
                )
            ];
        }

        public function getSymbol(): string {
            return "PRIMARY";
        }

        public function drop(): void {  
            $this
                ->table
                ->conn()
                ->alterTable($this->table->schema()->name(), $this->table->name())
                ->dropPrimaryKey()
                ->go();
        }

        public function exists(): bool {
            return
                DB::select([DB::raw("1")])
                    ->from($this->column->conn()->wrap("information_schema", "TABLE_CONSTRAINTS"))
                    ->where("TABLE_SCHEMA", $this->column->schema()->schema()->name())
                    ->where("TABLE_NAME", $this->column->table()->name())
                    ->where("CONSTRAINT_NAME", $this->getSymbol())
                    ->using($this->column->conn())
                    ->exists();
        }
    }
}

?>