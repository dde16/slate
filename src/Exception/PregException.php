<?php

namespace Slate\Exception {

    use Exception;
    use Throwable;

    class PregException extends Exception {
        public static function last(): void {
            if(preg_last_error() !== PREG_NO_ERROR)
                throw (new static(preg_last_error_msg(), preg_last_error()));
        }
    }
}

?>