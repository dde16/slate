<?php

namespace Slate\Sql\Modifier {
    trait TSqlBufferResultModifier {
        protected bool $bufferResultModifier = false;
    
        public function bufferResult() {
            $this->bufferResultModifier = true;

            return $this;
        }

        
        public function buildBufferResultModifier() {
            return (
                ($this->bufferResultModifier       ? "SQL_BUFFER_RESULT"     : null)
            );
        }
    }
}

?>