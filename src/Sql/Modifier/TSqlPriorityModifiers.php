<?php

namespace Slate\Sql\Modifier {
    trait TSqlPriorityModifiers {
        use TSqlLowPriorityModifier;
        use TSqlDelayedModifier;
        use TSqlHighPriorityModifier;

        public function buildPriorityModifiers(): ?string {
            return (
                $this->buildLowPriorityModifier()  ?: 
                $this->buildDelayedModifier()      ?: 
                $this->buildHighPriorityModifier()
            );
        }
    }
}

?>