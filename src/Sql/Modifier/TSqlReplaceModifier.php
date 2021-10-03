<?php

namespace Slate\Sql\Modifier {
    trait TSqlReplaceModifier {
        protected bool $replaceModifier = false;
    
        public function replace(): static {
            $this->replaceModifier = true;

            return $this;
        }

        
        public function buildReplaceModifier(): ?string {
            return (
                ($this->replaceModifier ? "REPLACE" : null)
            );
        }
    }
}

?>