<?php

namespace Slate\Sql\Modifier {
    trait TSqlAllModifier {
        protected bool $allModifier = false;
    
        public function all() {
            $this->allModifier = true;

            return $this;
        }

        
        public function buildAllModifier() {
            return (
                ($this->allModifier       ? "ALL"     : null)
            );
        }
    }
}

?>