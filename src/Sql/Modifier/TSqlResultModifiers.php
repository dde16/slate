<?php

namespace Slate\Sql\Modifier {
    trait TSqlResultModifiers {
        use TSqlBigResultModifier;
        use TSqlSmallResultModifier;
        use TSqlBufferResultModifier;

        
        public function buildResultModifiers() {
            return (
                ($this->smallResultModifier       ? "MYSQL_SMALL_RESULT"       : null) ?: 
                ($this->bigResultModifier         ? "MYSQL_BIG_RESULT"         : null) ?: 
                ($this->bufferResultModifier      ? "MYSQL_BUFFER_RESULT"      : null)
            );
        }
    }
}

?>