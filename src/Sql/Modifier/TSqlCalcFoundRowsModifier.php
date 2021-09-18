<?php

namespace Slate\Sql\Modifier {
    trait TSqlCalcFoundRowsModifier {
        protected bool $calcFoundRowsModifier = false;
    
        public function calcFoundRows() {
            $this->calcFoundRowsModifier = true;

            return $this;
        }

        
        public function buildCalcFoundRowsModifier() {
            return (
                ($this->calcFoundRowsModifier       ? "SQL_CALC_FOUND_ROWS"     : null)
            );
        }
    }
}

?>