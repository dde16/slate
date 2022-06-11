<?php declare(strict_types = 1);

namespace Slate\Sql\Statement\Trait {

    use Slate\Sql\Statement\SqlDropStatement;

    trait TSqlDropStatement {
        public function drop(): SqlDropStatement {
            return (new SqlDropStatement($this));
        }

        public function dropSchema(string $ref): SqlDropStatement {
            return $this->drop()->schema($ref);
        }

        public function dropTable(string $ref, ?string $subref = null): SqlDropStatement {
            return $this->drop()->table($ref, $subref);
        }
    }
}

?>