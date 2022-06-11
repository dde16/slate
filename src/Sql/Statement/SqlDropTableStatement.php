<?php

namespace Slate\Sql\Statement {
    use Slate\Sql\Statement\Trait\TSqlTableStatement;

    class SqlDropTableStatement extends SqlDropStatement {
        use TSqlTableStatement;

        protected string $type = "TABLE";
    }
}

?>