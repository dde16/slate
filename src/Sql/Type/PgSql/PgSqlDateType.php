<?php

namespace Slate\Sql\Type\PgSql {
    use Slate\Sql\Type\SqlDateType;

    class PgSqlDateType extends SqlDateType {
        public const FORMAT = \DateTime::RFC3339;
    }
}

?>