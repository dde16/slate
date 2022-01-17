<?php

namespace Slate\Sql {

    use Closure;
    use Generator;
    use PDO;
    use Slate\Data\IStringForwardConvertable;

    abstract class SqlConnection extends PDO {
        public const PREFIX = NULL;
        public const NAME = NULL;
        
        public const TOKEN_IDENTIFIER_DELIMITER = NULL;

        public    string  $hostname;
        public    string  $username;
        protected string  $password;
        protected ?int     $port;
        protected ?string $database;
        protected ?string $charset;
        protected PDO     $connection;

        protected array   $schemas = [];

        public function __construct(
            string $hostname,
            string $username,
            string $password,
            int    $port     = null,
            string $database = null,
            string $charset  = null

        ) {
            $this->hostname = $hostname;
            $this->username = $username;
            $this->password = $password;
            $this->port     = $port;
            $this->database = $database;
            $this->charset  = $charset;

            parent::__construct(
                static::PREFIX . ":" . \Arr::join(\Arr::mapAssoc(
                    \Arr::filter([
                        "host" => $this->hostname,
                        "dbname"   => $this->database,
                        "charset" => $this->charset,
                        "port" => $this->port
                    ]),
                    function($key, $value) {
                        return [$key, "$key=$value"];
                    }
                ), ";"),
                $username,
                $password
            );
        }

        public function table(string $schema, string $table, Closure $callback = null): SqlTable {
            return $this->schema($schema)->table($table, $callback);
        }

        public function schema(string $name, Closure $callback = null): SqlSchema {
            $schema = &$this->schema[$name];

            if($schema === null)
                $schema = new SqlSchema($this, $name);

            if($callback)
                $callback($schema);

            return $schema;
        }
        

        public function cbMultiquery(array|string $queries): void {
            $callbacks  = [];

            if(is_array($queries)) {
                $multiquery = [];

                foreach($queries as $index => $query) {
                    $callback = null;

                    if(is_array($query)) {
                        $multiquery[] = $query[0];

                        if($query[1] instanceof Closure) {
                            $callback = $query[1];
                        }
                        else if($query[1] !== null) {
                            throw new \Error(\Str::format(
                                "Invalid multiquery callback of type '{}' at index '{}'",
                                gettype($query[1]),
                                $index
                            ));
                        }
                    }
                    else  {
                        $multiquery[] = $query;
                    }

                    if($callback !== null) $callbacks[$index] = $callback;
                }

                $multiquery = \Arr::join($multiquery, "\n");
            }
            else {
                $multiquery = $queries;
            }

            $index = 0;
            
            if($statement = $this->prepare($multiquery)) {
                try {
                    if($statement->execute()) {
                        do {
                            if(($callback = $callbacks[$index++]))
                                $callback($statement);

                        } while($statement->nextRowset() === true);
                    }
                }
                catch(\PDOException $e) {
                    $e->queryString = $statement->queryString;
                    throw $e;
                }
            }
        }

        public function wrap(string ...$tokens): string {
            return \Arr::join(\Arr::map(
                $tokens,
                fn($token) => \Str::wrapc($token, static::TOKEN_IDENTIFIER_DELIMITER)
            ), ".");
        }

        public function multiquery(array|string $queries, bool $rows = false, bool $aggr = false): Generator {
            $multiquery = is_array($queries) ? \Arr::join(
                \Arr::map($queries, function($query) {
                    if(is_object($query)) {
                        assert(
                            \Cls::implements($query, IStringForwardConvertable::class),
                            new \Error(\Str::format(
                                "{} doesn't implement the {} interface, so cannot be converted to a string.",
                                \Cls::getName($query),
                                \Cls::getName(IStringForwardConvertable::class)
                            ))
                        );

                        $query = $query->toString();
                    }

                    return \Str::addSuffix(\Str::trim($query), ";");
                }),
                "\n"
            ) : $queries;
            
            if($statement = $this->prepare($multiquery)) {
                try {
                    if($statement->execute()) {
                        do {
                            if($rows) {
                                if($aggr) {
                                    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                                        yield $row;
                                    }
                
                                    $statement->fetch();
                                    // $statement->closeCursor();
                                }
                                else {
                                    yield $statement->fetchAll(PDO::FETCH_ASSOC);
                                }
                            }
                            else {
                                yield $statement;
                            }                            

                        } while($statement->nextRowset() === true);
                    }
                }
                catch(\PDOException $e) {
                    $e->queryString = $statement->queryString;
                    throw $e;
                }
            }
        }

        // public function prepare($query, array $binds = [], array $options = []) {
        //     $statement = parent::prepare($query, $options);

        //     foreach($binds as $name => $bind) {
        //         $type = match(\Any::getType($bind)) {
        //             "string" => PDO::PARAM_STR,
        //             "int"    => PDO::PARAM_INT,
        //             "float"  => PDO::PARAM_STR,
        //             "bool"   => PDO::PARAM_BOOL

        //         } ?: PDO::PARAM_NULL;

        //         if(\Arr::isAssocOffset($name)) {
        //             $statement->bindParam($name, $bind, $type);
        //         }
        //         else {
        //             $statement->bindValue($name+1, $bind, $type);
        //         }
        //     }

        //     return $statement;
        // }

        public function soloquery(mixed $query, bool $aggr = true): \Generator {
            $statement = $this->prepare($query);
            
            try {
                if($statement->execute()) {
                    while($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                        yield $row;
                    }

                    $statement->fetch();
                    $statement->closeCursor();
                }
                else {
                    // PDOException
                }
            }
            catch(\PDOException $e) {
                $e->queryString = $statement->queryString;
                throw $e;
            }
        }
    }
}

?>