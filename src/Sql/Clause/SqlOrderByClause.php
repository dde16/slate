<?php declare(strict_types = 1);

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

        public function buildSql(): ?array {
            $sql = $this->_buildOrderByClause();

            return $sql !== null ? [$sql]: null;
        }
    }
}

?>