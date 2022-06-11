<?php

namespace Slate\Sql\Statement\Contract {
    use Slate\Sql\Statement\SqlSelectStatement;

    interface ISqlSelectable {
        public function select(array $columns = []): SqlSelectStatement;
    }
}

?>