<?php

namespace Slate\Sql\Statement\Contract {
    use Slate\Sql\Statement\SqlDropStatement;

    interface ISqlTableDroppable {
        public function dropTable(string $ref, ?string $subref = null): SqlDropStatement;
    }
}

?>