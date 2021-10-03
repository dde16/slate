<?php

namespace Slate\Sql\Statement {
    trait TSqlCreateStatement {
        public static function create(): SqlCreateStatement {
            return new SqlCreateStatement;
        }
    }
}

?>