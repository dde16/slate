<?php

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlCommentClause;
    use Slate\Sql\Clause\TSqlMediumClause;
    use Slate\Sql\Modifier\TSqlIfNotExistsModifier;
    use Slate\Sql\Modifier\TSqlIgnoreModifier;
    use Slate\Sql\Modifier\TSqlReplaceModifier;
    use Slate\Sql\Modifier\TSqlTemporaryModifier;
    use Slate\Sql\SqlStatement;

    class SqlCreateSchemaStatement extends SqlStatement {
        use TSqlCharacterSetClause;
        use TSqlCollateClause;
        use TSqlIfNotExistsModifier;

        protected string $name;

        public function __construct(string $name) {
            $this->name = $name;
        }

        public function build(): array {
            return [
                "CREATE SCHEMA",
                $this->buildIfNotExistsModifier(),
                $this->name,
                $this->buildCharsetClause(),
                $this->buildCollateClause()
            ];
        }
    }
}

?>