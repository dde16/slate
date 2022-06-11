<?php declare(strict_types = 1);

namespace Slate\Sql\Statement\Trait {

    use Slate\Sql\Statement\SqlSelectStatement;

    trait TSqlSelectStatement {
        public function select(array $columns = []): SqlSelectStatement {
            return (new SqlSelectStatement())->columns($columns);
        }
    }
}

?>