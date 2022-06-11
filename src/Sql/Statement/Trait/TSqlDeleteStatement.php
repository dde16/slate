<?php declare(strict_types = 1);

namespace Slate\Sql\Statement\Trait {

    use Slate\Sql\Statement\SqlDeleteStatement;

    trait TSqlDeleteStatement {
        public function delete(string|object $reference = null): SqlDeleteStatement {
            return (new SqlDeleteStatement($this))->from($reference);
        }
    }
}

?>