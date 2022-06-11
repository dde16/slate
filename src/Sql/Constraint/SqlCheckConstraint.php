<?php declare(strict_types = 1);

namespace Slate\Sql\Constraint {

    use Slate\Sql\Medium\SqlTable;
    use Slate\Sql\SqlConstraint;
    
    class SqlCheckConstraint extends SqlConstraint {
        public final const SYMBOL_SHORTHAND = "CHK";

        protected string $expr;

        public function __construct(SqlTable $table, string $expr, ?string $symbol = null) {
            parent::__construct($table, $symbol);

            $this->expr = $expr;
        }

        public function buildSql(): array {
            return [
                "CONSTRAINT",
                $this->getSymbol(),
                "CHECK",
                \Str::wrapc($this->expr, "()")
            ];
        }
    }
}

?>