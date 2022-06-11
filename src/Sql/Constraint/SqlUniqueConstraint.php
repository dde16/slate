<?php declare(strict_types = 1);

namespace Slate\Sql\Constraint {
    class SqlUniqueConstraint extends SqlMultiColumnConstraint {
        public const SYMBOL_SHORTHAND = "UN";

        public function buildSql(): array {
            return [
                "CONSTRAINT",
                $this->getSymbol(),
                "UNIQUE KEY",
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
    }
}

?>