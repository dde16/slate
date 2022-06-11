<?php

namespace Slate\Sql\Statement\Contract {
    use Slate\Sql\Statement\SqlInsertStatement;

    interface ISqlInsertable {
        public function insert(array $rows = []): SqlInsertStatement;
    }
}

?>