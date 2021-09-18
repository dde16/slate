<?php

namespace Slate\Mvc {
    abstract class Result {
        protected bool $bypass = false;

        public function bypasses(): bool {
            return $this->bypass;
        }
    }
}

?>