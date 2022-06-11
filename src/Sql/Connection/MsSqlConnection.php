<?php declare(strict_types = 1);

namespace Slate\Sql\Connection {

use Slate\Sql\SqlConnection;

final class MsSqlConnection extends SqlConnection {
        public const NAME   = "mssql";
        public const PREFIX = "sqlsrv";

        public const IDENTIFIER = '[]';
    }
}

?>