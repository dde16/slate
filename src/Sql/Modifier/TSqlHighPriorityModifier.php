<?php

namespace Slate\Sql\Modifier {
    trait TSqlHighPriorityModifier {
        protected bool $highPriorityModifier = false;
      
        public function highPriority(): static {
            $this->highPriorityModifier = true;

            return $this;
        }

        
        public function buildHighPriorityModifier() {
            return (
                ($this->highPriorityModifier       ? "HIGH_PRIORITY"     : null)
            );
        }
    }
}

?>