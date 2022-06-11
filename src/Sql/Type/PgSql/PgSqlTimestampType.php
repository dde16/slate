<?php declare(strict_types = 1);

namespace Slate\Sql\Type\PgSql {
    use Slate\Sql\Type\SqlDateTimeType;

    class PgSqlTimestampType extends SqlDateTimeType {
        public const FORMAT = "Y-m-d H:i:s";
    }
}

?>