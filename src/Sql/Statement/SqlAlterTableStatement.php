<?php

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\Clause\TSqlFromClause;
    use Slate\Sql\Clause\TSqlMediumClause;
    use Slate\Sql\Modifier\TSqlIgnoreModifier;
    use Slate\Sql\Modifier\TSqlReplaceModifier;
    use Slate\Sql\Modifier\TSqlTemporaryModifier;
    use Slate\Sql\SqlStatement;

    class SqlAlterTableStatement extends SqlStatement {
        use TSqlCharacterSetClause;
        use TSqlCollateClause;

        protected string $schema;

        public function __construct(string $name) {
            $this->schema = $name;
        }

        public function build(): array {
            return [
                "ALTER SCHEMA",
                $this->schema,
                $this->buildCharsetClause(),
                $this->buildCollateClause()
            ];
        }
    }
}

?>