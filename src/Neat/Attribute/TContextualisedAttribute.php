<?php

namespace Slate\Neat\Attribute {
    trait TContextualisedAttribute {
        protected ?string $context = null;

        public function getContext(): ?string {
            return $this->context;
        }

        public function inContext(?string $objectContext = null): bool {
            return $this->context === $objectContext;
        }
    }
}

?>