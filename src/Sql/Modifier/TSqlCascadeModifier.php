<?php

namespace Slate\Sql\Modifier {
    trait TSqlCascadeModifier {
        protected bool $cascadeModifier = false;
    
        public function cascade(): static {
            $this->cascadeModifier = true;

            return $this;
        }

        
        public function buildCascadeModifier(): ?string {
            return (
                ($this->cascadeModifier ? "CASCADE" : null)
            );
        }
    }
}

?>