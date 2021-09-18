<?php

namespace Slate\Sql\Modifier {
    trait TSqlDistinctRowModifier {
        protected bool $distinctRowModifier = false;
    
        public function distinctRow() {
            $this->distinctRowModifier = true;

            return $this;
        }

        
        public function buildDistinctRowModifier() {
            return (
                ($this->distinctRowModifier       ? "DISTINCTROW"     : null)
            );
        }
    }
}

?>