<?php declare(strict_types = 1);

namespace Slate\Data\Contract {
    use Slate\Sql\Type\SqlType;

    interface ISqlForwardConvertable {
        function toSqlValue(SqlType $target): string;
        function fromSqlValue(SqlType $type, string $value): void;
    }
}

?>