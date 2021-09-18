<?php

namespace Slate\Sql\Clause {
    use Slate\Sql\SqlClause;

    class SqlOrderByClause extends SqlClause {
        use TSqlOrderByClause {
            TSqlOrderByClause::buildOrderByClause as protected _buildOrderByClause;
        }

        public function __construct(array $by, string $direction = "ASC") {
            $this->orderBy = $by;
            $this->orderDirection = $direction;
        }

        public function toString(): string {
            return $this->_buildOrderByClause();
        }

    }
}

?>