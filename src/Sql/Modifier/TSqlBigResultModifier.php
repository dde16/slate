<?php

namespace Slate\Sql\Modifier {
    trait TSqlBigResultModifier {
        protected bool $bigResultModifier = false;
    
        public function bigResult() {
            $this->bigResultModifier = true;

            return $this;
        }

        
        public function buildBigResultModifier() {
            return (
                ($this->bigResultModifier       ? "SQL_BIG_RESULT"     : null)
            );
        }
    }
}

?>