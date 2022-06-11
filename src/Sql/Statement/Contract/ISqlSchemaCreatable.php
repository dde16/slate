<?php

namespace Slate\Sql\Statement\Contract {
    use Slate\Sql\Statement\SqlCreateSchemaStatement;

    interface ISqlSchemaCreatable {
        public function createSchema(string $schema): SqlCreateSchemaStatement;
    }
}

?>