<?php

namespace Slate\Sql\Statement {
    trait TSqlAlterStatement {
        public static function alter(): SqlAlterStatement {
            return new SqlAlterStatement;
        }
    }
}

?>