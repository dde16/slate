<?php

namespace Slate\Sql\Modifier {
    trait TSqlSetDefaultModifier {
        protected bool $setDefault = false;

        public function buildSetDefaultModifier(): ?string {
            return $this->setDefault ? "SET DEFAULT" : null;
        }
    }
}

?>