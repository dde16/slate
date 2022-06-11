<?php declare(strict_types = 1);

namespace Slate\Exception {
    class UndefinedRoutineException extends SlateException {
        public const ERROR_UNDEFINED_METHOD   = (1<<0);
        public const ERROR_UNDEFINED_FUNCTION = (1<<1);

        public const ERROR_MESSAGES       = [
            self::ERROR_UNDEFINED_FUNCTION => "Call to undefined method {function}().",
            self::ERROR_UNDEFINED_METHOD   => "Call to undefined method {class}::{method}()."
        ];
    }
}

?>