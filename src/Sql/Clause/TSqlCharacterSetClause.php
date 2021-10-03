<?php

namespace Slate\Sql\Clause {
    trait TSqlCharacterSetClause {
        protected ?string $charset = null;

        public function charset(string $name): static{
            $this->charset = $name;

            return $this;
        }

        public function buildCharsetClause(): ?string {
            return $this->charset ? "CHARACTER SET {$this->charset}" : null;
        }
    }
}

?>