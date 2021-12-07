<?php

namespace Slate\Sql\Constraint {

    use Slate\Sql\SqlColumn;
    use Slate\Sql\SqlConstraint;

    abstract class SqlColumnConstraint extends SqlConstraint {
        public function __construct(SqlColumn $column, string $symbol = null) {
            parent::__construct($symbol ?? ($column->getName()."_".static::SYMBOL_SHORTHAND));

            $this->column = $column;
        }
    }
}

?>