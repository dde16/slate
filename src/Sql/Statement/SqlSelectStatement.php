<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlStatement;

    use Slate\Sql\Modifier\TSqlUniquenessModifiers;

    use Slate\Sql\Modifier\TSqlHighPriorityModifier;

    use Slate\Sql\Modifier\TSqlStraightJoinModifier;

    use Slate\Sql\Modifier\TSqlResultModifiers;

    use Slate\Sql\Modifier\TSqlNoCacheModifier;
    use Slate\Sql\Modifier\TSqlCalcFoundRowsModifier;

    use Slate\Sql\Expression\TSqlColumnsExpression;

    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Clause\TSqlJoinClause;
    use Slate\Sql\Clause\TSqlGroupByWithRollupClause;
    use Slate\Sql\Clause\TSqlOrderByWithRollupClause;
    use Slate\Sql\Clause\TSqlLimitClause;

    use Slate\Facade\DB;

    use Slate\Sql\ISqlResultProvider;

    class SqlSelectStatement extends SqlStatement implements ISqlResultProvider {
        use TSqlUniquenessModifiers;

        use TSqlHighPriorityModifier;
        use TSqlStraightJoinModifier;

        use TSqlResultModifiers;

        use TSqlNoCacheModifier;
        use TSqlCalcFoundRowsModifier;

        use TSqlColumnsExpression;

        use TSqlFromClause;
        use TSqlWhereClause;
        use TSqlJoinClause;
        use TSqlGroupByWithRollupClause;
        use TSqlOrderByWithRollupClause;
        use TSqlLimitClause;

        use TSqlSelectStatementCount;

        use TSqlSelectStatementGet;
        use TSqlSelectStatementExists;
        use TSqlSelectStatementTake;
        use TSqlSelectStatementCount;
        use TSqlSelectStatementFirst;
        use TSqlSelectStatementPluck;
        use TSqlSelectStatementChunk;

        public function __clone() {
            if($this->wheres !== null)
                $this->wheres = clone $this->wheres;
        }

    
        public function build(): array {
            return [
                "SELECT",
                $this->buildUniquenessModifiers(),
                $this->buildHighPriorityModifier(),
                $this->buildStraightJoinModifier(),
                $this->buildResultModifiers(),
                $this->buildNoCacheModifier(),
                $this->buildColumnsExpression(),
                $this->buildFromClause(),
                $this->buildJoinClause(),
                $this->buildWhereClause(),
                $this->buildGroupByClause(),
                $this->buildOrderByClause(),
                $this->buildLimitClause()
            ];
        }




    }
}

?>