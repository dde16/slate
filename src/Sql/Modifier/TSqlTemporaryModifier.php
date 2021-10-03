<?php

namespace Slate\Sql\Modifier {
    trait TSqlTemporaryModifier {
        protected bool $temporaryModifier = false;
    
        public function temporary(): static {
            $this->temporaryModifier = true;

            return $this;
        }

        
        public function buildTemporaryModifier(): ?string {
            return (
                ($this->temporaryModifier ? "TEMPORARY" : null)
            );
        }
    }
}

?>