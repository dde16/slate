<?php

namespace Slate\Sql\Constraint {

    use Slate\Sql\Clause\TSqlReferencesClause;
    use Slate\Sql\SqlConstraint;


    class SqlForeignKeyConstraint extends SqlColumnConstraint {
        public const SYMBOL_SHORTHAND = "FK";

        use TSqlReferencesClause;

        public function build(): array {
            $indexBuild = ($this->buildIndex());
            $indexFirst = \Arr::slice($indexBuild, 0, 2);
            $indexLast  = \Arr::slice($indexBuild, 3);

            return [
                "CONSTRAINT",
                $this->symbol,
                "FOREIGN",
                ...$indexFirst,
                \Str::wrapc($this->column, "()"),
                ...$indexLast,
                $this->buildReferencesClause()
            ];
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