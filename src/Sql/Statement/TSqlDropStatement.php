<?php

namespace Slate\Sql\Statement {
    trait TSqlDropStatement {
        public static function drop(): object {
            return (new SqlDropStatement());
        }
    }
}

?>