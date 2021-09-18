<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlStatement;

    use Slate\Sql\Modifier\TSqlLowPriorityModifier;
    use Slate\Sql\Modifier\TSqlQuickModifier;
    use Slate\Sql\Modifier\TSqlIgnoreModifier;

    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlLimitClause;

    use Slate\Facade\DB;
    use Slate\Mvc\App;
    use Slate\Sql\ISqlResultProvider;

    class SqlDeleteStatement extends SqlStatement  {
        use TSqlLowPriorityModifier;
        use TSqlQuickModifier;
        use TSqlIgnoreModifier;

        use TSqlFromClause;
        use TSqlWhereClause;
        use TSqlOrderByClause;
        use TSqlLimitClause;
    
        public function build(): array {
            return [
                "DELETE",
                $this->buildLowPriorityModifier(),
                $this->buildQuickModifier(),
                $this->buildIgnoreModifier(),
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