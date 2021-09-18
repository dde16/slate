<?php

namespace Slate\Sql\Modifier {
    trait TSqlSmallResultModifier {
        protected bool $smallResultModifier = false;
    
        public function smallResult() {
            $this->smallResultModifier = true;

            return $this;
        }

        
        public function buildSmallResultModifier() {
            return (
                ($this->smallResultModifier       ? "MYSQL_SMALL_RESULT"     : null)
            );
        }
    }
}

?>