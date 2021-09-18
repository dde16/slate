<?php

namespace Slate\Exception {
    class SqlConnectionException extends SqlException {
        public $code = 1005;

        public $format    = "Connection error at {username}@{hostname}:{port}";

        public function __construct($arg) {
            parent::__construct(
                $arg
            );
        }
    }
}


?>