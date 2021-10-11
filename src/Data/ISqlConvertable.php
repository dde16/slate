<?php

namespace Slate\Data {
    use Slate\Sql\Type\SqlType;

    interface ISqlForwardConvertable {
        function toSqlValue(SqlType $target): string;
        function fromSqlValue(SqlType $type, string $value): void;
    }
}

?>