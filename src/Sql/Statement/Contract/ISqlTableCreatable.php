<?php

namespace Slate\Sql\Statement\Contract {
    use Slate\Sql\Statement\SqlCreateTableStatement;

    interface ISqlTableCreatable {
        public function createTable(string $ref, ?string $subref = null): SqlCreateTableStatement;
    }
}

?>