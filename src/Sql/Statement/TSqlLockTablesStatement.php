<?php

namespace Slate\Sql\Statement {
    trait TSqlLockTablesStatement {
        public static function lock(): object {
            return (new SqlLockTablesStatement());
        }
    }
}

?>