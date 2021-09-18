<?php

namespace Slate\Sql\Modifier {
    trait TSqlStraightJoinModifier {
        protected bool $straightJoinModifier = false;
    
        public function straightJoin() {
            $this->straightJoinModifier = true;

            return $this;
        }

        
        public function buildStraightJoinModifier() {
            return (
                ($this->straightJoinModifier       ? "STRAIGHT_JOIN"     : null)
            );
        }
    }
}

?>