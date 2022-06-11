<?php declare(strict_types = 1);

namespace Slate\Sql\Type {
    interface ISqlTypeForwardConvertable {
        function toSqlValue(mixed $value): string;
    }
}

?>