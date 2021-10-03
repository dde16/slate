<?php

namespace Slate\Sql\Modifier {
    trait TSqlIfNotExistsModifier {
        protected bool $ifNotExistsModifier = false;
        
        public function ifNotExists(): static {
            $this->ifNotExistsModifier = true;

            return $this;
        }

        public function buildIfNotExistsModifier(): ?string {
            return $this->ifNotExistsModifier ? "IF NOT EXISTS" : null;
        }
    }
}

?>