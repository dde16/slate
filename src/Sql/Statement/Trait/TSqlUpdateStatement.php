<?php declare(strict_types = 1);

namespace Slate\Sql\Statement\Trait {
    use Slate\Sql\Statement\SqlUpdateStatement;

    trait TSqlUpdateStatement {
        public function update(string $ref, ?string $subref = null): SqlUpdateStatement {
            return (new SqlUpdateStatement($this, $ref, $subref));
        }
    }
}

?>