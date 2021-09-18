<?php

namespace Slate\Sql\Statement {
    trait TSqlDeleteStatement {
        public static function delete(string|object $reference = null): object {
            return (new SqlDeleteStatement())->from($reference);
        }
    }
}

?>