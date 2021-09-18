<?php

namespace Slate\Sql\Clause {


    trait TSqlGroupByWithRollupClause {
        use TSqlGroupByClause {
            buildGroupByClause as buildGroupByClauseWithoutRollup;
        }

        protected bool  $groupByWithRollup = false;

        public function groupByRollup(): static {
            $this->groupByWithRollup = true;

            return $this;
        }

        public function buildGroupByClause(): string|null {
            return ($groupByClause = $this->buildGroupByClauseWithoutRollup()) !== null
                ?  $groupByClause . ($this->groupByWithRollup ? " WITH ROLLUP" : "")
                : null;
        }
    }

}

?>