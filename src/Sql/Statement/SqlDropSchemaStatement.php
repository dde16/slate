<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\Statement\Trait\TSqlSchemaStatement;

    class SqlDropSchemaStatement extends SqlDropStatement {
        use TSqlSchemaStatement;

        protected string $type = "SCHEMA";
    }
}

?>