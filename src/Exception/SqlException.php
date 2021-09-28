<?php

namespace Slate\Exception {

use Slate\Data\IStringForwardConvertable;
    use Throwable;

class SqlException extends SlateException {
        public const ERROR_MESSAGES = [
            SlateException::ERROR_DEFAULT => "Error {code} ({state}): {message} for '{query}'."
        ];

        public int                              $mysqlCode  = null;
        public int                              $mysqlState = null;
        public string|IStringForwardConvertable $mysqlQuery = null;

        public function __construct(array|string $argument = null, int $code = 0, ?Throwable $previous = nul) {
            if($argument !== null) {
                if(is_string($argument["query"])) {
                    $argument["query"] = base64_encode($argument["query"]);
                    
                    $this->mysqlQuery = $argument["query"];
                }

                if(is_int($argument["code"])) {
                    $this->mysqlCode = $argument["code"];
                }

                if(is_int($argument["state"])) {
                    $this->mysqlState = $argument["state"];
                }
            }

            parent::__construct(
                $argument,
                $code,
                $previous
            );
        }
    }
}

// 1146, 42S02

?>