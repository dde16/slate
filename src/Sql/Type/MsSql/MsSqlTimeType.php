<?php declare(strict_types = 1);

namespace Slate\Sql\Type\MsSql {
    use Slate\Sql\Type\SqlTimeType;

    class MsSqlTimeType extends SqlTimeType {
        public const FORMAT = "H:i:s\.u";
    }
}

?>