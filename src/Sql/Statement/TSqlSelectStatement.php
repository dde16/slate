<?php

namespace Slate\Sql\Statement {
    trait TSqlSelectStatement {
        public static function select(array $columns = []): SqlSelectStatement {
            return (new SqlSelectStatement())->columns($columns);
        }
    }
}

?>