<?php declare(strict_types = 1);

namespace Slate\Sql\Type {
    class SqlTimeType extends SqlDateTimeType {
        public const DATE   = FALSE;
        public const TIME   = TRUE;
    }
}

?>