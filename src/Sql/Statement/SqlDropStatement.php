<?php

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlMediumClause;
    
    
    
    
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;

    class SqlDropStatement extends SqlStatement {
        use TSqlMediumClause;

        public const MODIFIERS =
            SqlModifier::RESTRICT
            | SqlModifier::CASCADE
            | SqlModifier::TEMPORARY
            | SqlModifier::IF_EXISTS
        ;

        public function build(): array {
            return [
                "DROP",
                $this->buildModifier(SqlModifier::RESTRICT),
                $this->type,
                $this->medium,
                $this->buildModifier(SqlModifier::IF_EXISTS),
                $this->buildModifier(SqlModifier::RESTRICT)
                    ?: $this->buildModifier(SqlModifier::CASCADE)
            ];
        }

    }
}

?>