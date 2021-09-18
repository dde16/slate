<?php

namespace Slate\Sql {
    final class MsSqlConnection extends SqlConnection {
        public const NAME   = "mssql";
        public const PREFIX = "sqlsrv";

        public const TOKEN_IDENTIFIER_DELIMITER = '[]';
    }
}

?>