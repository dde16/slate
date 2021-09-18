<?php

namespace Slate\Mvc\Result {
    use Slate\Mvc\Result;

    abstract class DataResult extends Result {
        protected string $mime;

        public function getMime(): string {
            return $this->mime;
        }
        
        public abstract function toString(): string;
    }
}

?>