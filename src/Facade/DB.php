<?php

namespace Slate\Facade {
    use Slate\Utility\Facade;
    use Slate\Utility\TMiddleware;
    use Slate\Mvc\Env;

    use Slate\Sql\Statement\TSqlSelectStatement;
    use Slate\Sql\Statement\TSqlDeleteStatement;
    use Slate\Sql\Statement\TSqlInsertStatement;
    use Slate\Sql\Statement\TSqlUpdateStatement;
    use Slate\Sql\Statement\TSqlDropStatement;

    use Slate\Sql\MySqlConnection;
    use Slate\Sql\SqlConnection;
    use Slate\Sql\SqlRaw;
    use Slate\Sql\Statement\TSqlAlterStatement;
    use Slate\Sql\Statement\TSqlCreateStatement;

final class DB extends Facade {
        use TMiddleware;

        use TSqlSelectStatement;
        use TSqlDeleteStatement;
        use TSqlUpdateStatement;
        use TSqlInsertStatement;
        use TSqlDropStatement;
        use TSqlAlterStatement;
        use TSqlCreateStatement;

        public const SUPPORTED = ["mysql"];

        protected static string $default;
        protected static array  $connections = [];

        protected static array $middleware = [
            "mysql" => MySqlConnection::class
        ];
        
        protected static array $using = [
            "mysql" => MySqlConnection::class
        ];

        public static function raw(string $content): SqlRaw {
            return(new SqlRaw($content));
        }

        public static function prepare(string $query, array $binds = []): void {
            $conn = static::default();

            $statement = $conn->prepare($query, $binds);
            
            $statement->execute();
        }

        public static function default(string $name = null): SqlConnection|null {
            if($name === null) {
                return static::$connections[static::$default];
            }

            if(\Arr::hasKey(static::$connections, $name))
                throw new \Error("No connection by that name to be default.");

            static::$default = $name;

            return null;
        }

        public static function add(string $name, SqlConnection $connection, bool $default = false): void {
            if(\Arr::hasKey(static::$connections, $name))
                throw new \Error("This connection is already in use.");

            if($default) static::default($name);

            static::$connections[$name] = $connection;
        }

        public static function utilise(string $name): SqlConnection {
            return static::conn($name);
        }
        
        public static function conn(string $name): SqlConnection {
            if(!\Arr::hasKey(static::$connections, $name))
                throw new \Error("No connection by that name exists.");

            return static::$connections[$name];
        }

        public static function import(string $driver, bool $default = false): void {
            if(!\Arr::contains(DB::SUPPORTED, $driver))
                throw new \Error("SQL server '$driver' is unsupported.");

            $connectionClass = DB::use("mysql");
            
            static::add(
                $driver,
                new $connectionClass(
                    Env::get("$driver.hostname", [ "important" => true ]),
                    Env::get("$driver.username", [ "important" => true ]),
                    Env::get("$driver.password", [ "important" => true ]),
                    Env::get("$driver.port"),
                    Env::get("$driver.database")
                ),
                $default
            );
        }
    }
}

?>