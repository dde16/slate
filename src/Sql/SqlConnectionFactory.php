<?php

namespace Slate\Sql {

    use Slate\Sql\Connection\MsSqlConnection;
    use Slate\Sql\Connection\MySqlConnection;
    use Slate\Sql\Connection\PgSqlConnection;
    use Slate\Utility\Factory;

    class SqlConnectionFactory extends Factory {
        public const MAP = [
            MySqlConnection::NAME        => MySqlConnection::class,
            MySqlConnection::PREFIX      => MySqlConnection::class,

            MsSqlConnection::NAME        => MsSqlConnection::class,
            MsSqlConnection::PREFIX      => MsSqlConnection::class,

            PgSqlConnection::NAME        => PgSqlConnection::class,
            PgSqlConnection::PREFIX      => PgSqlConnection::class
        ];
    }
}

?>