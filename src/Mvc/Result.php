<?php declare(strict_types = 1);

namespace Slate\Mvc {
    abstract class Result {
        protected bool $bypass;

        public function __construct(bool $bypass = false) {
            $this->bypass = $bypass;
        }

        public function bypass(bool $bypass): void {
            $this->bypass = $bypass;
        }

        public function bypasses(): bool {
            return $this->bypass;
        }
    }
}

?>