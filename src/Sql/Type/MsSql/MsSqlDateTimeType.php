<?php

namespace Slate\Sql\Type\MsSql {
    use Slate\Sql\Type\SqlDateTimeType;

    class MsSqlDateTimeType extends SqlDateTimeType {
        public const FORMAT = "Y-m-d H:i:s\.v";
    }
}

?>