<?php

namespace Slate\Sql\Statement\Contract {

    use Slate\Sql\Statement\SqlAlterSchemaStatement;

    interface ISqlSchemaAlterable {
        public function alterSchema(string $schema): SqlAlterSchemaStatement;
    }
}

?>