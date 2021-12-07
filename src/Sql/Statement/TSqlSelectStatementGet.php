<?php

namespace Slate\Sql\Statement {
    use Slate\Facade\DB;
    use Slate\Facade\App;

trait TSqlSelectStatementGet {
        public function get() {
            $query = $this->toString();
            $conn = $this->conn();

            foreach($conn->soloquery($query) as $row) {
                yield $row;
            }
        }
    }
}

?>