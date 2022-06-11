<?php declare(strict_types = 1);

namespace Slate\Sql {

    use Closure;
    use Generator;
    use PDO;
    use PDOException;
    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Sql\Medium\SqlSchema;
    use Slate\Sql\Medium\SqlTable;

    use Slate\Sql\Statement\Contract\ISqlDeletable;
    use Slate\Sql\Statement\Contract\ISqlInsertable;
    use Slate\Sql\Statement\Contract\ISqlSchemaAlterable;
    use Slate\Sql\Statement\Contract\ISqlSchemaCreatable;
    use Slate\Sql\Statement\Contract\ISqlSchemaDroppable;
    use Slate\Sql\Statement\Contract\ISqlSelectable;
    use Slate\Sql\Statement\Contract\ISqlTableAlterable;
    use Slate\Sql\Statement\Contract\ISqlTableCreatable;
    use Slate\Sql\Statement\Contract\ISqlTableDroppable;
    use Slate\Sql\Statement\Contract\ISqlTableLockable;
    use Slate\Sql\Statement\Contract\ISqlUpdatable;

    use Slate\Sql\Statement\Trait\TSqlAlterStatement;
    use Slate\Sql\Statement\Trait\TSqlCreateStatement;
    use Slate\Sql\Statement\Trait\TSqlDeleteStatement;
    use Slate\Sql\Statement\Trait\TSqlDropStatement;
    use Slate\Sql\Statement\Trait\TSqlInsertStatement;
    use Slate\Sql\Statement\Trait\TSqlLockTablesStatement;
    use Slate\Sql\Statement\Trait\TSqlSelectStatement;
    use Slate\Sql\Statement\Trait\TSqlUpdateStatement;
    use Slate\Sql\Statement\Trait\TSqlShowStatement;

    abstract class SqlConnection
        extends PDO
        implements
            ISqlDeletable,
            ISqlInsertable,
            ISqlSchemaAlterable,
            ISqlSchemaCreatable,
            ISqlSchemaDroppable,
            ISqlSelectable,
            ISqlTableAlterable,
            ISqlTableCreatable,
            ISqlTableDroppable,
            ISqlTableLockable,
            ISqlUpdatable
    {
        use TSqlAlterStatement;
        use TSqlCreateStatement;
        use TSqlDeleteStatement;
        use TSqlDropStatement;
        use TSqlInsertStatement;
        use TSqlLockTablesStatement;
        use TSqlSelectStatement;
        use TSqlUpdateStatement;
        use TSqlShowStatement;

        public const PREFIX = NULL;
        public const NAME = NULL;
        public const IDENTIFIER = NULL;

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

        public function table(string $ref, string $subref = null): SqlTable {
            if($subref === null) {
                $table = $ref;

                if(($schema = $this->getDefaultSchema()) === null) {
                    throw new PDOException("No default schema.");
                }
            }
            else {
                $schema = $ref;
                $table  = $subref;
            }

            return $this->schema($schema)->table($table);
        }

        public function hasSchema(string $name): bool {
            return \Arr::hasKey($this->schema, $name);
        }

        public function discard(): void {
            foreach($this->schema as $schema) {
                $schema->discard();
            }
        }

        public function schema(string $name): SqlSchema {
            $schema = &$this->schema[$name];

            if($schema === null)
                $schema = new SqlSchema($this, $name);

            return $schema;
        }

        public function getDefaultSchema(): ?string {
            return $this->database;
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

        /**
         * Unwrap a wrapped identifier.
         *
         * @param string $token
         *
         * @return array
         */
        public function unwrap(string $token): array {
            [$prefix, $centre, $suffix] = \Str::divide(static::IDENTIFIER);

            return \Arr::map(
                \Str::split($token, "."),
                fn(string $token): string => \Str::removeSuffix(\Str::removePrefix($token, $prefix), $suffix)
            );
        }

        /**
         * Wrap a series of tokens into an identifier.
         *
         * @param string ...$tokens
         *
         * @return string
         */
        public function wrap(string ...$tokens): string {
            return \Arr::join(\Arr::map($tokens, fn(string $token): string => \Str::wrapc($token, static::IDENTIFIER)), ".");
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

        public function transact(Closure $closure): void {
            $this->beginTransaction();

            try {
                $closure();
            }
            catch(\PDOException $e) {
                if(!$this->rollBack())
                    throw new \Error("Unable to rollback.");

                throw $e;
            }

            $this->commit();
        }

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