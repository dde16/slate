<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlStatement;

    
    
    

    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlLimitClause;

    use Slate\Facade\DB;
    use Slate\Facade\App;
    use Slate\Sql\ISqlResultProvider;
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
    
        public function build(): array {
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
            $conn = App::conn($this->conn);

            return $conn->prepare($this->toString())->execute();
        }
    }
}

?>