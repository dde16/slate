<?php

namespace Slate\Sql\Statement\Contract {
    use Slate\Sql\Statement\SqlLockTablesStatement;

    interface ISqlTableLockable {
        public function lock(): SqlLockTablesStatement;
    }
}

?>