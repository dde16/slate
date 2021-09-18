<?php

namespace Slate\Sql\Modifier {
    trait TSqlDistinctModifier {
        protected bool $distinctModifier = false;
    
        public function distinct() {
            $this->distinctModifier = true;

            return $this;
        }

        
        public function buildDistinctModifier() {
            return (
                ($this->distinctModifier       ? "DISTINCT"     : null)
            );
        }
    }
}

?>