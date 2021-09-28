<?php

namespace Slate\Exception {
    class StackUnderflowException extends SlateException {
        public const ERROR_MESSAGES = [
            StackUnderflowException::ERROR_DEFAULT => "An attempt was made to pop a stack that was empty."
        ];
    }
}

?>