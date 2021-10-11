<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlStatement;

    
    
    

    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlSetClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlLimitClause;

    use Slate\Facade\DB;
    use Slate\Mvc\App;
    use Slate\Sql\ISqlResultProvider;
    use Slate\Sql\SqlModifier;

    class SqlUpdateStatement extends SqlStatement  {
        public const MODIFIERS = SqlModifier::LOW_PRIORITY | SqlModifier::IGNORE;

        use TSqlWhereClause;
        use TSqlFromClause;
        use TSqlOrderByClause;
        use TSqlLimitClause;
        use TSqlSetClause;
    
        public function build(): array {
            return [
                "UPDATE",
                $this->buildFroms(),
                $this->buildModifier(SqlModifier::LOW_PRIORITY),
                $this->buildModifier(SqlModifier::IGNORE),
                $this->buildSetClause(),
                $this->buildWhereClause(),
                $this->buildLimitClause()
            ];
        }
    }
}

?>