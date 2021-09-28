<?php

namespace Slate\Exception {
    class StackOverflowException extends SlateException {
        public const ERROR_MESSAGES = [
            StackOverflowException::ERROR_DEFAULT => "An attempt was made to push to a stack that was full."
        ];
    }
}

?>