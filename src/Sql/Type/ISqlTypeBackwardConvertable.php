<?php

namespace Slate\Sql\Type {
    interface ISqlTypeBackwardConvertable {
        function fromSqlValue(string $value, string $target): mixed;
    }
}

?>