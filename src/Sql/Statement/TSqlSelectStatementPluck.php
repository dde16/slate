<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {
    trait TSqlSelectStatementPluck {
        public function pluck(string $column) {
            $this->columns = [];
            $this->column($column);

            foreach($this->get() as $row) {
                yield $row[$column];
            }
        }
    }
}

?>