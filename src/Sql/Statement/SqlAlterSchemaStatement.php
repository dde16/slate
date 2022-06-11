<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    use Slate\Sql\SqlStatement;
    use Slate\Sql\Statement\Trait\TSqlSchemaStatement;

    class SqlAlterSchemaStatement extends SqlStatement {
        use TSqlCharacterSetClause;
        use TSqlCollateClause;
        use TSqlSchemaStatement;

        public function buildSql(): array {
            return [
                "ALTER SCHEMA",
                $this->name,
                $this->buildCharsetClause(),
                $this->buildCollateClause()
            ];
        }
    }
}

?>