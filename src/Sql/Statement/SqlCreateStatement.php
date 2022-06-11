<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {
    class SqlCreateStatement  {

        public function table(string $name): SqlCreateTableStatement {
            return (new SqlCreateTableStatement($this->conn, $name));
        }

        public function schema(string $name): SqlCreateSchemaStatement {
            return (new SqlCreateSchemaStatement($this->conn, $name));
        }
    }
}

?>