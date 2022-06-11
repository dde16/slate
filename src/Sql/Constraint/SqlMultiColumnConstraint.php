<?php declare(strict_types = 1);

namespace Slate\Sql\Constraint {

    use Slate\Sql\Medium\SqlTable;
    use Slate\Sql\SqlConstraint;

    abstract class SqlMultiColumnConstraint extends SqlColumnConstraint {
        public function getColumns(): array {
            return $this->columns;
        }
    }
}

?>
