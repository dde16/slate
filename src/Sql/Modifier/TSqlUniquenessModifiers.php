<?php

namespace Slate\Sql\Modifier {
    trait TSqlUniquenessModifiers {
        use TSqlAllModifier;
        use TSqlDistinctModifier;
        use TSqlDistinctRowModifier;

        
        public function buildUniquenessModifiers() {
            return (
                $this->buildAllModifier()
                ?: $this->buildDistinctModifier()
                ?: $this->buildDistinctRowModifier()
            );
        }
    }
}

?>