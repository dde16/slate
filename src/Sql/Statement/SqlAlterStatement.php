<?php

namespace Slate\Sql\Statement {
    class SqlAlterStatement {
        public function schema(string $ref): SqlAlterSchemaStatement {
            return(new SqlAlterSchemaStatement($ref));
        }

        public function table(string $ref): SqlAlterTableStatement {
            return(new SqlAlterTableStatement($ref));
        }
    }
}

?>