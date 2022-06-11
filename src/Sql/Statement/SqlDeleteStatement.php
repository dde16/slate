<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlStatement;

    
    
    

    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlLimitClause;

    use Slate\Facade\App;
    use Slate\Sql\SqlModifier;

    class SqlDeleteStatement extends SqlStatement  {
        use TSqlFromClause;
        use TSqlWhereClause;
        use TSqlOrderByClause;
        use TSqlLimitClause;

        public const MODIFIERS =
            SqlModifier::LOW_PRIORITY
            | SqlModifier::QUICK
            | SqlModifier::IGNORE
        ;
    
        public function buildSql(): array {
            return [
                "DELETE",
                ...$this->buildModifiers([
                    SqlModifier::LOW_PRIORITY,
                    SqlModifier::QUICK,
                    SqlModifier::IGNORE
                ]),
                $this->buildFromClause(),
                $this->buildWhereClause(),
                $this->buildOrderByClause(),
                $this->buildLimitClause()
            ];
        }

        public function go(): bool  {
            return $this->conn->prepare($this->toSql())->execute();
        }
    }
}

?>