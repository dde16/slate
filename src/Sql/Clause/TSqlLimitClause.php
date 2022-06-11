<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    trait TSqlLimitClause {
        public mixed $limit = null;
        public mixed $offset = null;

        public function limit(int|string $limit, int|string $offset = null): static {
            $this->limit = $limit;
            $this->offset = $offset;

            return $this;
        }

        public function offset(int|string $offset): static {
            $this->offset = $offset;

            return $this;
        }
        
        public function buildLimitClause(): string|null {
            return $this->limit !== null
                ? "LIMIT " . strval($this->limit) . (($this->offset !== null) ? " OFFSET " . strval($this->offset) : "")
                : null;
        }
    }
}

?>