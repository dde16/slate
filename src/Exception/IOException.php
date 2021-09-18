<?php

namespace Slate\Exception {
    class IOException extends SlateException {
        public $code   = 1006;
        public $format = "An IO error occured when accessing '{path}'.";

        public function __construct($arg) {
            parent::__construct(
                $arg
            );
        }
    }
}


?>