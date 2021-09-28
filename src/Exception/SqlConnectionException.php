<?php

namespace Slate\Exception {
    class SqlConnectionException extends SqlException {
        public const ERROR_MESSAGES = [
            SqlConnectionException::ERROR_DEFAULT => "Connection error at {username}@{hostname}:{port}"
        ];
    }
}


?>