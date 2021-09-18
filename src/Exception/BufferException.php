<?php

namespace Slate\Exception {
    class BufferException extends SlateException {
        public $code   = 1010;
        public $format = "An error occured while {action} a buffer.";
    }
}

?>