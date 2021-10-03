<?php

namespace Slate\Sql\Statement {
    class SqlCreateStatement  {

        public function table(string $name): SqlCreateTableStatement {
            return (new SqlCreateTableStatement($name));
        }

        public function schema(string $name): SqlCreateSchemaStatement {
            return (new SqlCreateSchemaStatement($name));
        }
    }
}

?>