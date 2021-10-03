<?php

namespace Slate\Sql\Modifier {
    trait TSqlRestrictModifier {
        protected bool $restrictModifier = false;
    
        public function restrict(): static {
            $this->restrictModifier = true;

            return $this;
        }

        
        public function buildRestrictModifier(): ?string {
            return (
                ($this->restrictModifier ? "CASCADE" : null)
            );
        }
    }
}

?>