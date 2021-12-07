<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlConstruct;
    use Slate\Sql\SqlStatement;
    
    use Slate\Sql\Expression\TSqlColumnsExpression;

    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Clause\TSqlJoinClause;
    use Slate\Sql\Clause\TSqlGroupByWithRollupClause;
    use Slate\Sql\Clause\TSqlOrderByWithRollupClause;
    use Slate\Sql\Clause\TSqlLimitClause;

    use Slate\Facade\DB;

    use Slate\Sql\ISqlResultProvider;
    use Slate\Sql\SqlModifier;

class SqlSelectStatement extends SqlStatement implements ISqlResultProvider {
        public const MODIFIERS =
            SqlModifier::ALL
            | SqlModifier::DISTINCT
            | SqlModifier::DISTINCT_ROW
            
            | SqlModifier::HIGH_PRIORITY
            | SqlModifier::STRAIGHT_JOIN
            
            | SqlModifier::BIG_RESULT
            | SqlModifier::SMALL_RESULT
            | SqlModifier::BUFFER_RESULT

            | SqlModifier::CALC_FOUND_ROWS
            | SqlModifier::NO_CACHE;

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
                ...$this->buildModifiers([
                    SqlModifier::ALL,
                    SqlModifier::DISTINCT,
                    SqlModifier::DISTINCT_ROW,

                    SqlModifier::HIGH_PRIORITY,
                    SqlModifier::STRAIGHT_JOIN,
                    SqlModifier::BIG_RESULT,
                    SqlModifier::SMALL_RESULT,
                    SqlModifier::BUFFER_RESULT,
                    SqlModifier::NO_CACHE
                ]),
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