<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlCharacterSetClause;
    use Slate\Sql\Clause\TSqlCollateClause;
    
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;
    use Slate\Sql\Statement\Trait\TSqlSchemaStatement;

    class SqlCreateSchemaStatement extends SqlStatement {
        use TSqlCharacterSetClause;
        use TSqlCollateClause;
        use TSqlSchemaStatement;

        public const MODIFIERS = SqlModifier::IF_NOT_EXISTS;

        public function buildSql(): array {
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