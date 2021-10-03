<?php

namespace Slate\Mvc {
    abstract class Result {
        protected bool $bypass;

        public function __construct(bool $bypass = false) {
            $this->bypass = $bypass;
        }

        public function bypasses(): bool {
            return $this->bypass;
        }
    }
}

?>