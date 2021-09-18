<?php

namespace Slate\Sql\Modifier {
    trait TSqlDelayedModifier {
        protected bool $delayedModifier = false;
    
        public function delayed() {
            $this->delayedModifier = true;

            return $this;
        }

        
        public function buildDelayedModifier() {
            return (
                ($this->delayedModifier       ? "DELAYED"     : null)
            );
        }
    }
}

?>