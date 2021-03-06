<?php declare(strict_types = 1);

namespace Slate\Sql\Type\PgSql {
    use Slate\Sql\Type\SqlDateType;

    class MsSqlDateType extends SqlDateType {
        public const FORMAT = "Y-m-d";
    }
}

?>