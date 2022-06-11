<?php

namespace Slate\Sql\Statement {
    class SqlShowStatement extends SqlSelectStatement {
        public function buildSql(): array {
            return ["SHOW", ...\Arr::slice(
                parent::buildSql(),
                1
            )];
        }
    }
}

?>