<?php declare(strict_types = 1);

namespace Slate\Sql\Statement\Trait {

    use Slate\Sql\Statement\SqlCreateSchemaStatement;
    use Slate\Sql\Statement\SqlCreateStatement;
    use Slate\Sql\Statement\SqlCreateTableStatement;

    trait TSqlCreateStatement {
        public function create(): SqlCreateStatement {
            return new SqlCreateStatement($this);
        }

        public function createSchema(?string $ref = null): SqlCreateSchemaStatement {
            return new SqlCreateSchemaStatement($this, $ref);
        }

        public function createTable(string $ref, ?string $subref = null): SqlCreateTableStatement {
            return new SqlCreateTableStatement($this, $ref, $subref);
        }
    }
}

?>