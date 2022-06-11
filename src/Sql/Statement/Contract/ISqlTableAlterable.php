<?php

namespace Slate\Sql\Statement\Contract {
    use Slate\Sql\Statement\SqlAlterTableStatement;

    interface ISqlTableAlterable {
        public function alterTable(string $ref, ?string $subref = null): SqlAlterTableStatement;
    }
}

?>