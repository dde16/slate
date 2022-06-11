<?php

namespace Slate\Sql\Statement\Contract {
    use Slate\Sql\Statement\SqlUpdateStatement;

    interface ISqlUpdatable {
        public function update(string $ref, ?string $subref = null): SqlUpdateStatement;
    }
}

?>