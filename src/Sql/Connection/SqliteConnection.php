<?php

namespace Slate\Sql\Connection {

    use PDOException;
    use Slate\Sql\SqlConnection;

    class SqliteConnection extends SqlConnection {
        public const NAME   = "sqlite";
        public const PREFIX = "sqlite";
        public const IDENTIFIER = "``";

        public function __construct(
            string $dsn,
            ?string $username = null,
            ?string $password = null,
            ?array $options = null
        )
        {
            parent::__construct($dsn, $username, $password, $options);

            $path = \Str::afterFirst($dsn, "sqlite:");

            $key = ftok($path, "a");
            $this->sem = sem_get($key);
        }

        public function beginTransaction(): bool {
            if(sem_acquire($this->sem) === false)
                throw new PDOException("Unable to begin the transaction, unable to acquire semaphore for Sqlite database.");

            return parent::beginTransaction();
        }
        
        public function commit(): bool {
            $success = parent::commit();
            sem_release($this->sem);
            return $success;
        }

        public function rollBack(): bool {
            $success = parent::rollBack();
            sem_release($this->sem);
            return $success;
        }
    }
}

?>