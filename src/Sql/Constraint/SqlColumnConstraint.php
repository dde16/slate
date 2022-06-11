<?php declare(strict_types = 1);

namespace Slate\Sql\Constraint {

    use Slate\Sql\Medium\SqlTable;
    use Slate\Sql\SqlColumn;
    use Slate\Sql\SqlConstraint;

    abstract class SqlColumnConstraint extends SqlConstraint {
        /**
         * List of columns associated with this constraint.
         *
         * @var array
         */
        public array $columns = [];

        public function __construct(SqlTable $table, array $columns = [], ?string $symbol = null) {
            parent::__construct($table, $symbol);
            $this->columns = $columns;
        }
    }
}

?>