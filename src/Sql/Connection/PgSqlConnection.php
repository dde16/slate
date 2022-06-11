<?php declare(strict_types = 1);

namespace Slate\Sql\Connection {

    use Slate\Sql\SqlConnection;

    final class PgSqlConnection extends SqlConnection {
        public const NAME   = "pgsql";
        public const PREFIX = "pgsql";
        public const IDENTIFIER = '""';
    }
}

?>