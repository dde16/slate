<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {

    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Sql\SqlClause;

    class SqlPartitionByClause extends SqlClause {
        protected string|IStringForwardConvertable $expr;

        public function __construct(string|IStringForwardConvertable $expr) {
            $this->expr = $expr;
        }

        public function buildSql(): ?array {
            $expr = (is_object($this->expr) ? $this->expr->toString() : $this->expr);

            if(empty($expr))
                return null;

            return ["PARTITION BY", $expr];
        }
    }
}

?>