<?php declare(strict_types = 1);

namespace Slate\Sql\Type\MsSql {
    use Slate\Sql\Type\SqlDateTimeType;

    class MsSqlDateTime2Type extends SqlDateTimeType {
        public const FORMAT = "Y-m-d H:i:s\.u";
    }
}

?>