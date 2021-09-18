<?php

namespace Slate\Sql\Statement {
    use Slate\Facade\DB;
    use Slate\Mvc\App;

trait TSqlSelectStatementGet {
        public function get() {
            $query = $this->toString();
            $conn = App::conn($this->conn);

            foreach($conn->soloquery($query) as $row) {
                yield $row;
            }
        }
    }
}

?>