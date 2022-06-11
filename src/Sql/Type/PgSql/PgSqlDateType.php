<?php declare(strict_types = 1);

namespace Slate\Sql\Type\PgSql {
    use Slate\Sql\Type\SqlDateType;

    class PgSqlDateType extends SqlDateType {
        public const FORMAT = \DateTime::RFC3339;
    }
}

?>