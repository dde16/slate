<?php declare(strict_types = 1);

namespace Slate\Mvc\Result {
    class ScalarResult extends DataResult {
        protected string|int|bool|float $data;

        public function __construct(string|int|bool|float $data, string $mime = "text/html", bool $bypass = false) {
            $this->data   = $data;
            $this->mime   = $mime;
            $this->bypass = $bypass;
        }

        public function toString(): string {
            return \Str::repr($this->data);
        }
    }
}

?>