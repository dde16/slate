<?php

namespace Slate\Exception {
    class InvalidContextException extends SlateException {
        public $code   = 1002;
        public $format = "Invalid PHP context of '{context}'.";

        public function __construct($arg) {
            parent::__construct(
                $arg
            );
        }
    }
}


?>