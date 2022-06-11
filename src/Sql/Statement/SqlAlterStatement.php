<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {

    use Slate\Sql\SqlConnection;
    use Slate\Sql\Trait\TSqlUsingConnection;

    class SqlAlterStatement {
        use TSqlUsingConnection;

        public function __construct(SqlConnection $conn) {
            $this->conn = $conn;
        }

        public function schema(string $ref): SqlAlterSchemaStatement {
            return(new SqlAlterSchemaStatement($this->conn, $ref));
        }

        public function table(string $ref, ?string $subref = null): SqlAlterTableStatement {
            return(new SqlAlterTableStatement($this->conn, $ref, $subref));
        }
    }
}

?>