<?php

namespace Slate\Sql\Statement\Contract {

    use Slate\Sql\Statement\SqlDeleteStatement;

    interface ISqlDeletable {
        public function delete(): SqlDeleteStatement;
    }
}

?>