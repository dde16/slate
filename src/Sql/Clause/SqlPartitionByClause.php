<?php

namespace Slate\Sql\Clause {

    use Slate\Data\IStringForwardConvertable;
    use Slate\Sql\SqlClause;

    class SqlPartitionByClause extends SqlClause {
        protected string|IStringForwardConvertable $expr;

        public function __construct(string|IStringForwardConvertable $expr) {
            $this->expr = $expr;
        }

        public function toString(): string {
            return \Arr::join(\Arr::filter([
                "PARTITION BY",
                (is_object($this->expr) ? $this->expr->toString() : $this->expr)
            ]), " ");
        }
    }
}

?>