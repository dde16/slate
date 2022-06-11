<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {

use Slate\Data\Contract\IStringForwardConvertable;

    trait TSqlPartitionByClause {
        public array $partitionByExprs = [];

        public function partitionBy(string|IStringForwardConvertable ...$exprs) {

            foreach($exprs as $index => $expr) {
                if(is_object($expr)) {
                    assert(
                        \Cls::implements($expr, IStringForwardConvertable::class),
                        new \Error(\Str::format(
                            "Partition By expr of class {} at index {}, must implement the {} interface to be convertable to a string.",
                            \Cls::getName($expr),
                            $index,
                            \Cls::getName(IStringForwardConvertable::class)
                        ))
                    );
                }

                $this->partitionByExprs[] = $expr;
            }

            return $this;
        }

        public function buildPartitionByClause(): ?string {
            return !\Arr::isEmpty($this->partitionByExprs) ? (
                "PARTITION BY "
                . \Arr::join(
                    \Arr::map(
                        $this->partitionByExprs,
                        function($partitionByExpr) {
                            return is_object($partitionByExpr) ? $partitionByExpr->toString() : $partitionByExpr;
                        }
                    ),
                    ", "
                )
            ) : null;
        }
    }
}

?>