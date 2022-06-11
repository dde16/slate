<?php declare(strict_types = 1);

namespace Slate\Sql\Statement\Trait {

    use Slate\Sql\SqlConnection;
    use Slate\Sql\Statement\SqlAlterSchemaStatement;
    use Slate\Sql\Statement\SqlAlterStatement;
    use Slate\Sql\Statement\SqlAlterTableStatement;

    trait TSqlAlterStatement {
        public function alter(): SqlAlterStatement {
            return(new SqlAlterStatement($this));
        }

        public function alterSchema(string $ref = null): SqlAlterSchemaStatement {
            return $this->alter()->schema($ref);
        }

        public function alterTable(string $ref, string $subref = null): SqlAlterTableStatement {
            return $this->alter()->table($ref, $subref);
        }
    }
}

?>