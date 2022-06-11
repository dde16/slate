<?php

namespace Slate\Sql\Statement\Trait {

    use PDOException;
    use Slate\Sql\SqlConnection;

    trait TSqlSchemaStatement {
        protected string $name;

        public function __construct(SqlConnection $conn, string $name = null) {
            parent::__construct($conn);

            if($name === null)
                if(($name = $conn->getDefaultSchema()) === null)
                    throw new PDOException("No schema was specified to this statement and the connection doesn't have a schema to fall back to.");

            $this->name = $name;
        }
    }
}

?>