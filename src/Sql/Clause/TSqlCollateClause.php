<?php

namespace Slate\Sql\Clause {
    trait TSqlCollateClause {
        protected ?string $collation = null;

        public function collate(string $name): static {
            $this->collation = $name;

            return $this;
        }

        public function buildCollateClause(): ?string {
            return $this->collation ? "COLLATE {$this->collation}" : null;
        }
    }
}

?>