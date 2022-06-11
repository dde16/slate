<?php declare(strict_types = 1);

namespace Slate\Sql\Type {
    class SqlDateType extends SqlDateTimeType {
        public const DATE   = TRUE;
        public const TIME   = FALSE;
    }
}

?>