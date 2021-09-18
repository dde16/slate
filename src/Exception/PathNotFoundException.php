<?php

namespace Slate\Exception {
    class PathNotFoundException extends SlateException {
        public $code   = 1001;
        public $format = "Path '{path}' not found.";

        public function __construct($arg) {
            parent::__construct(
                $arg
            );
        }
    }
}


?>