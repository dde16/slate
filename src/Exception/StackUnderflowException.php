<?php

namespace Slate\Exception {
    class StackUnderflowException extends SlateException {
        public $code   = 1012;
        public $format = "An attempt was made to pop a stack that was empty.";
    }
}

?>