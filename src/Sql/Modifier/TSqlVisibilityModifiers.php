<?php

namespace Slate\Sql\Modifier {
    trait TSqlVisibilityModifiers {
        protected ?string $visibility = null;

        public function visible(): static {
            $this->visibility = "VISIBLE";

            return $this;
        }

        public function invisible(): static {
            $this->visibility = "INVISIBLE";

            return $this;
        }
    }
}

?>