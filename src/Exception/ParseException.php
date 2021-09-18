<?php

namespace Slate\Exception {
    class ParseException extends SlateException {
        public $code   = 1009;
        public $format = "Unable to parse this value for type {type}.";
    }
}


?>