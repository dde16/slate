<?php

namespace Slate\Exception {
    class SqlException extends SlateException {
        public $code       = 1003;
        public $format     = "Error {code} ({state}): {message} for '{query}'.";
        public $mysqlCode  = null;
        public $mysqlState = null;
        public $mysqlQuery = null;

        public function __construct($arg) {
            if(is_string($arg["query"])) {
                $arg["query"] = base64_encode($arg["query"]);
                
                $this->mysqlQuery = $arg["query"];
            }

            if(is_int($arg["code"])) {
                $this->mysqlCode = $arg["code"];
            }

            if(is_int($arg["state"])) {
                $this->mysqlState = $arg["state"];
            }

            parent::__construct(
                $arg
            );
        }
    }
}

// 1146, 42S02

?>