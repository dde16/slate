<?php

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlMediumClause;
    
    
    
    
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;

    class SqlCreateSchemaStatement extends SqlStatement {
        use TSqlCharacterSetClause;
        use TSqlCollateClause;

        public const MODIFIERS = SqlModifier::IF_NOT_EXISTS;

        protected string $name;

        public function __construct(string $name) {
            $this->name = $name;
        }

        public function build(): array {
            return [
                "CREATE SCHEMA",
                $this->buildModifier(SqlModifier::IF_NOT_EXISTS),
                $this->name,
                $this->buildCharsetClause(),
                $this->buildCollateClause()
            ];
        }
    }
}

?>