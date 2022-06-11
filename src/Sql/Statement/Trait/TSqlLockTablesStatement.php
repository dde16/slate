<?php declare(strict_types = 1);

namespace Slate\Sql\Statement\Trait {

    use Slate\Sql\Statement\SqlLockTablesStatement;

    trait TSqlLockTablesStatement {
        public function lock(): SqlLockTablesStatement {
            return (new SqlLockTablesStatement($this->conn));
        }
    }
}

?>