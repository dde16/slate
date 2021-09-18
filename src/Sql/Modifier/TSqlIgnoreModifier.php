<?php

namespace Slate\Sql\Modifier {
    trait TSqlIgnoreModifier {
        protected bool $ignoreModifier = false;
    
        public function ignore() {
            $this->ignoreModifier = true;

            return $this;
        }

        
        public function buildIgnoreModifier() {
            return (
                ($this->ignoreModifier       ? "IGNORE"     : null)
            );
        }
    }
}

?>