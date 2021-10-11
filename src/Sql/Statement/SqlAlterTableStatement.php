<?php

namespace Slate\Sql\Statement {

    use Slate\Sql\Clause\TSqlDropAuxiliariesClause;
    use Slate\Sql\Clause\TSqlRenameAuxiliariesClause;
    use Slate\Sql\Clause\TSqlRenameClause;
    
    use Slate\Sql\SqlModifier;
    use Slate\Sql\SqlStatement;

    class SqlAlterTableStatement extends SqlStatement {
        use TSqlDropAuxiliariesClause;
        use TSqlRenameAuxiliariesClause;
        use TSqlRenameClause;

        public const MODIFIERS = SqlModifier::FORCE;

        protected array $additions = [];

        public function build(): array {
            return [
                "ALTER TABLE",
                $this->name,
                $this->buildDropAuxiliariesClause(),
                $this->buildRenameClause(),
                $this->buildRenameAuxiliariesClause(),
                $this->buildModifier(SqlModifier::FORCE)
            ];
        }
    }
}

?>