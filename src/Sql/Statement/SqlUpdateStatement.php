<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlStatement;

    use Slate\Sql\Modifier\TSqlLowPriorityModifier;
    use Slate\Sql\Modifier\TSqlQuickModifier;
    use Slate\Sql\Modifier\TSqlIgnoreModifier;

    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlSetClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlLimitClause;

    use Slate\Facade\DB;
    use Slate\Mvc\App;
    use Slate\Sql\ISqlResultProvider;

    class SqlUpdateStatement extends SqlStatement  {
        use TSqlLowPriorityModifier;
        use TSqlIgnoreModifier;

        use TSqlWhereClause;
        use TSqlFromClause;
        use TSqlOrderByClause;
        use TSqlLimitClause;
        use TSqlSetClause;
    
        public function build(): array {
            return ["UPDATE", $this->buildFroms(), $this->buildLowPriorityModifier(), $this->buildIgnoreModifier(), $this->buildSetClause(), $this->buildWhereClause(), $this->buildLimitClause()];
        }

        public function go(): bool {
            
            $conn = App::conn($this->conn);

            return $conn->prepare($this->toString())->execute();
        }
    }
}

?>