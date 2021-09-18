<?php

namespace Slate\Sql\Modifier {
    trait TSqlLowPriorityModifier {
        protected bool $lowPriorityModifier = false;
    
        public function lowPriority() {
            $this->lowPriorityModifier = true;

            return $this;
        }

        
        public function buildLowPriorityModifier() {
            return (
                ($this->lowPriorityModifier       ? "LOW_PRIORITY"     : null)
            );
        }
    }
}

?>