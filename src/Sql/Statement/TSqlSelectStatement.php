<?php

namespace Slate\Sql\Statement {
    trait TSqlSelectStatement {
        public static function select(array $columns = []): object {
            return (new SqlSelectStatement())->columns($columns);
        }
    }
}

?>