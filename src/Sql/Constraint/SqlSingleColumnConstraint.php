<?php declare(strict_types = 1);

namespace Slate\Sql\Constraint {

    use Slate\Sql\Medium\SqlTable;
    use Slate\Sql\SqlColumn;
    use Slate\Sql\SqlConstraint;

    abstract class SqlSingleColumnConstraint extends SqlColumnConstraint {
        public function getColumn(): ?string {
            return $this->columns[0];
        }
    }
}

?>