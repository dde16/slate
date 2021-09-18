<?php

namespace Slate\Sql\Modifier {
    trait TSqlNoCacheModifier {
        protected bool $noCacheModifier = false;
    
        public function noCache() {
            $this->noCacheModifier = true;

            return $this;
        }

        
        
        public function buildNoCacheModifier() {
            return (
                ($this->noCacheModifier       ? "SQL_NO_CACHE"     : null)
            );
        }
    }
}

?>