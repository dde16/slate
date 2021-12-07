<?php

namespace Slate\Sql\Constraint {

    use Slate\Facade\DB;
    use Slate\Sql\SqlColumn;

    class SqlPrimaryKeyConstraint extends SqlColumnConstraint {
        public function __construct(SqlColumn $column) {
            parent::__construct($column, "PRIMARY");
        }

        public function build(): array {
            return [
                "CONSTRAINT",
                $this->symbol,
                "PRIMARY",
                ...($this->buildIndex()),
                \Str::wrapc($this->column, "()")
            ];
        }

        public function exists(): bool {
            return
                DB::select([DB::raw("1")])
                    ->from($this->column->conn()->wrap("information_schema", "TABLE_CONSTRAINTS"))
                    ->where("TABLE_SCHEMA", $this->column->schema()->schema()->name())
                    ->where("TABLE_NAME", $this->column->table()->name())
                    ->where("CONSTRAINT_NAME", $this->symbol)
                    ->using($this->column->conn())
                    ->exists();
        }
    }
}

?>