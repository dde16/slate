<?php

namespace Slate\Sql\Modifier {
    trait TSqlIfExistsModifier {
        protected bool $ifExistsModifier = false;
        
        public function ifNotExists(): static {
            $this->ifExistsModifier = true;

            return $this;
        }

        public function buildIfExistsModifier(): ?string {
            return $this->ifExistsModifier ? "IF EXISTS" : null;
        }
    }
}

?>