<?php declare(strict_types = 1);

namespace Slate\Sql\Type\MySql {
    use Slate\Sql\Type\SqlDateTimeType;

    class MySqlDateTimeType extends SqlDateTimeType {
        public const FORMAT = "Y-m-d H:i:s";
    }
}

?>