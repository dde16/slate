<?php

namespace Slate\Sql\Modifier {
    trait TSqlQuickModifier {
        protected bool $quickModifier = false;
    
        public function quick() {
            $this->quickModifier = true;

            return $this;
        }

        
        public function buildQuickModifier() {
            return (
                ($this->quickModifier       ? "QUICK"     : null)
            );
        }
    }
}

?>