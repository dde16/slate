<?php

namespace Slate\Exception {
    class StackOverflowException extends SlateException {
        public $code   = 1011;
        public $format = "An attempt was made to push to a stack that was full.";
    }
}

?>