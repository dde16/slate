<?php declare(strict_types = 1);

namespace Slate\Sql\Type\PgSql {
    use Slate\Sql\Type\SqlTimeType;

    class PgSqlTimeType extends SqlTimeType {
        public const FORMAT = "H:i:s\.u";
    }
}

?>