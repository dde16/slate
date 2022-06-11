<?php

namespace Slate\Sql\Statement\Contract {
    use Slate\Sql\Statement\SqlDropStatement;

    interface ISqlSchemaDroppable {
        public function dropSchema(string $schema): SqlDropStatement;
    }
}

?>