<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {
    use Slate\Sql\SqlStatement;
    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlSetClause;
    use Slate\Sql\Clause\TSqlWhereClause;
    use Slate\Sql\Clause\TSqlOrderByClause;
    use Slate\Sql\Clause\TSqlLimitClause;
    use Slate\Sql\SqlModifier;
    use Slate\Sql\Statement\Trait\TSqlTableStatement;

    class SqlUpdateStatement extends SqlStatement  {
        use TSqlTableStatement;

        public const MODIFIERS = SqlModifier::LOW_PRIORITY | SqlModifier::IGNORE;

        use TSqlWhereClause;
        use TSqlFromClause;
        use TSqlOrderByClause;
        use TSqlLimitClause;
        use TSqlSetClause;
    
        public function buildSql(): array {
            return [
                "UPDATE",
                $this->name,
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