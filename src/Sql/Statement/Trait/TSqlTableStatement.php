<?php

namespace Slate\Sql\Statement\Trait {

    use PDOException;
    use Slate\Sql\SqlConnection;

    trait TSqlTableStatement {
        protected string $name;

        public function __construct(SqlConnection $conn, string $ref, ?string $subref = null) {
            parent::__construct($conn);

            $refs  = [$ref];

            if($subref !== null)
                $refs[] = $subref;

            $this->name = $conn->wrap(...$refs);
        }
    }
}

?>