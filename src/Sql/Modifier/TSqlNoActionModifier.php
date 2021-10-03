<?php

namespace Slate\Sql\Modifier {
    trait TSqlNoActionModifier {
        protected bool $noAction = false;

        public function buildNoActionModifier(): ?string {
            return $this->noAction ? "NO ACTION" : null;
        }
    }
}

?>