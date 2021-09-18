<?php

namespace Slate\Sql\Statement {
    trait TSqlUpdateStatement {
        public static function update(string|object $reference = null): object {
            return (new SqlUpdateStatement())->table($reference);
        }
    }
}

?>