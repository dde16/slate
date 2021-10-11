<?php

namespace Slate\Sql\Clause {
    trait TSqlRenameClause {
        protected ?string $rename = null;


        public function rename(string $to): static {
            $this->rename = $to;

            return $this;
        }

        public function buildRenameClause(): ?string {
            return
                $this->rename !== null
                    ? "RENAME TO {$this->rename}"
                    : null
            ;
        }

    }
}

?>