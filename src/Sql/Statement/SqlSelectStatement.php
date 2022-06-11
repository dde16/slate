<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlStatement;
    
    use Slate\Sql\Expression\TSqlColumnsExpression;

    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlJoinClause;
    use Slate\Sql\Clause\TSqlGroupByWithRollupClause;
    use Slate\Sql\Clause\TSqlOrderByWithRollupClause;
    use Slate\Sql\Clause\TSqlLimitClause;

    use Slate\Sql\Clause\TSqlWhereInlineClause;
    use Slate\Sql\Condition\SqlBlockCondition;
    use Slate\Sql\SqlModifier;

    /** @method void where(Closure $where) */
    /** @method void where(string $column, $value) */
    /** @method void where(string $column, string $operator, $value) */
    /** @method void orWhere(Closure $where) */
    /** @method void orWhere(string $column, $value) */
    /** @method void orWhere(string $column, string $operator, $value) */
    /** @method void andWhere(Closure $where) */
    /** @method void andWhere(string $column, $value) */
    /** @method void andWhere(string $column, string $operator, $value) */
    class SqlSelectStatement extends SqlStatement {
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
        // use TSqlWhereClause;
        use TSqlWhereInlineClause;
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
        use TSqlSelectStatementPaginate;
    
        public function __construct() {
            $this->wheres = new SqlBlockCondition(["logical" => "AND"]);
            $this->refs   = [&$this->wheres->children];
        }

        public function __clone() {
            if($this->wheres !== null)
                $this->wheres = clone $this->wheres;
        }

    
        public function buildSql(): array {
            $build = [
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

            return $build;
        }




    }
}

?>