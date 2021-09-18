<?php

namespace Slate\Sql\Type {
    interface ISqlTypeForwardConvertable {
        function toSqlValue(mixed $value): string;
    }
}

?>