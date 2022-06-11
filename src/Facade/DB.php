<?php declare(strict_types = 1);

namespace Slate\Facade {
    use Slate\Utility\Facade;
    use Slate\Mvc\Env;
    use Slate\Sql\Connection\SqlConnectionFactory;
    use Slate\Sql\SqlRaw;
    
    final class DB extends Facade {
        public static function __callStatic(string $name, array $arguments): mixed {
            return App::conn()->{$name}(...$arguments);
        }

        public static function raw(string $content): SqlRaw {
            return(new SqlRaw($content));
        }

        public static function prepare(string $query, array $binds = []): void {
            $conn = static::default();

            $statement = $conn->prepare($query, $binds);
            
            $statement->execute();
        }

        public static function import(string $driver, bool $default = false): void {
            static::add(
                $driver,
                SqlConnectionFactory::create($driver, [
                    Env::string("$driver.hostname", assert: true),
                    Env::string("$driver.username", assert: true),
                    Env::string("$driver.password", assert: true),
                    Env::string("$driver.port"),
                    Env::string("$driver.database")
                ]),
                $default
            );
        }
    }
}

?>