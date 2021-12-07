<?php

namespace Slate\Sql\Connection {

use Slate\Sql\SqlConnection;

final class PgSqlConnection extends SqlConnection {
        public const NAME   = "pgsql";
        public const PREFIX = "pgsql";

        public const TOKEN_IDENTIFIER_DELIMITER = '``';
    }
}

?>