<?php

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlMediumClause;
    use Slate\Sql\Modifier\TSqlCascadeModifier;
    use Slate\Sql\Modifier\TSqlIfExistsModifier;
    use Slate\Sql\Modifier\TSqlRestrictModifier;
    use Slate\Sql\Modifier\TSqlTemporaryModifier;
    use Slate\Sql\SqlStatement;

    class SqlDropStatement extends SqlStatement {
        use TSqlMediumClause;
        
        use TSqlRestrictModifier;
        use TSqlCascadeModifier;
        use TSqlTemporaryModifier;
        use TSqlIfExistsModifier;

        public function build(): array {
            return [
                "DROP",
                $this->buildTemporaryModifier(),
                $this->type,
                $this->medium,
                $this->buildIfExistsModifier(),
                (
                    $this->buildRestrictModifier()
                    ?: $this->buildCascadeModifier()
                )
            ];
        }

    }
}

?>