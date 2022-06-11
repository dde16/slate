<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    trait TSqlIntoClause {
        use TSqlFromClause {
            TSqlFromClause::from as into;
            TSqlFromClause::buildFroms as buildFroms;
        }

        
        public function buildIntoClause(): string|null {
            return !\Arr::isEmpty($this->froms)
                ? "INTO " . $this->buildFroms()
                : null;
        }
    }
}

?>