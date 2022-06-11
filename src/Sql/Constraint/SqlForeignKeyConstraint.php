<?php declare(strict_types = 1);

namespace Slate\Sql\Constraint {

    use Slate\Sql\Clause\TSqlReferencesClause;
    use Slate\Sql\SqlConstraint;


    class SqlForeignKeyConstraint extends SqlSingleColumnConstraint {
        public const SYMBOL_SHORTHAND = "FK";

        use TSqlReferencesClause;

        public function buildSql(): array {
            return [
                "CONSTRAINT",
                $this->getSymbol(),
                "FOREIGN",
                \Str::wrapc($this->table->conn()->wrap($this->getColumn()), "()"),
                $this->buildReferencesClause()
            ];
        }

        public function drop(): void {  
            $this
                ->table
                ->conn()
                ->alterTable($this->table->schema()->name(), $this->table->name())
                ->dropForeignKey($this->symbol)
                ->go();
        }

        public function fromArray(array $array): void {
            parent::fromArray($array);

            $this->foreignSchema = $array["foreign_schema"];
            $this->foreignTable  = $array["foreign_table"];
            $this->foreignColumn = $array["foreign_column"];

            $this->match         = $array["match"];
            $this->onDelete      = $array["on_delete"];
            $this->onUpdate      = $array["on_update"];
        }
    }
}

?>