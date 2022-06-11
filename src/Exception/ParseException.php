<?php declare(strict_types = 1);

namespace Slate\Exception {
    class ParseException extends SlateException {
        public const ERROR_URI_PARSE        = (1<<1);

        public const ERROR_MESSAGES = [
            ParseException::ERROR_URI_PARSE => "Unable to parse uri '{uri}'."
        ];
    }
}


?>