<?php declare(strict_types = 1);

namespace Slate\Sql\Constraint {

    use Slate\Sql\Constraint\SqlCheckConstraint;
    use Slate\Sql\Constraint\SqlForeignKeyConstraint;
    use Slate\Sql\Constraint\SqlPrimaryKeyConstraint;
    use Slate\Sql\Constraint\SqlUniqueConstraint;
    use Slate\Utility\Factory;

    class SqlConstraintFactory extends Factory {
        public const MAP = [
            "CHECK"       => SqlCheckConstraint::class,
            "PRIMARY KEY" => SqlPrimaryKeyConstraint::class,
            "FOREIGN KEY" => SqlForeignKeyConstraint::class,
            "UNIQUE"      => SqlUniqueConstraint::class
        ];
    }
}

?>