<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    trait TSqlLikeClause {
        protected ?string $like = null;

        public function like(string $like): ?string {
            $this->like = $like;

            return $like;
        }

        public function buildLikeClause(): ?string {
            return $this->like ? "LIKE {$this->like}" : null;
        }
    }
}

?>