<?php

namespace Slate\Sql\Modifier {
    trait TSqlSetNullModifier {
        protected bool $setNull = false;

        public function buildSetNullModifier(): ?string {
            return $this->setNull ? "SET NULL" : null;
        }
    }
}

?>