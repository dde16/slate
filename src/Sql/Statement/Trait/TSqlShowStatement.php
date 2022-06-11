<?php declare(strict_types = 1);

namespace Slate\Sql\Statement\Trait {

    use Slate\Sql\Statement\SqlShowStatement;

    trait TSqlShowStatement {
        public function show(array $columns = []): SqlShowStatement {
            return (new SqlShowStatement())->columns($columns);
        }
    }
}

?>