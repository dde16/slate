<?php declare(strict_types = 1);

namespace Slate\Sql\Type {
    interface ISqlTypeBackwardConvertable {
        function fromSqlValue(string $value, string $target): mixed;
    }
}

?>