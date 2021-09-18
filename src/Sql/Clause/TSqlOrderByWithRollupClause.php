<?php

namespace Slate\Sql\Clause {


    trait TSqlOrderByWithRollupClause {
        use TSqlOrderByClause {
            buildOrderByClause as buildOrderByClauseWithoutRollup;
        }

        protected bool $orderByWithRollup = false;

        public function orderByRollup(): static {
            $this->orderByWithRollup = true;

            return $this;
        }
        
        public function buildOrderByClause(): ?string {
            return ($orderByClause = $this->buildOrderByClauseWithoutRollup()) !== null
                ?  $orderByClause  . ($this->orderByWithRollup ? " WITH ROLLUP" : "")
                : null;
        }
    }

}

?>