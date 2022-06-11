<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    trait TSqlCollateClause {
        protected ?string $collation = null;

        public function collate(string $name): static {
            $this->collation = $name;

            return $this;
        }

        public function getCollation(): string {
            return $this->collation;
        }

        public function buildCollateClause(): ?string {
            return $this->collation ? "COLLATE {$this->collation}" : null;
        }
    }
}

?>