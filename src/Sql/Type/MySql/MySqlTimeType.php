<?php declare(strict_types = 1);

namespace Slate\Sql\Type\MySql {
    use Slate\Sql\Type\SqlTimeType;

    class MySqlTimeType extends SqlTimeType {
        public const FORMAT = "H:i:s";
    }
}

?>