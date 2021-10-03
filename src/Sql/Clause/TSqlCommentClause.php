<?php

namespace Slate\Sql\Clause {
    trait TSqlCommentClause {
        protected ?string $comment = null;

        public function comment(string $name): static {
            $this->comment = $name;

            return $this;
        }

        public function buildCommentClause(): ?string {
            return $this->comment ? "COMMENT='{$this->comment}'" : null;
        }
    }
}

?>