<?php declare(strict_types = 1);

namespace Slate\Sql {
    interface ISqlInferredType {
        static function inferSqlType(string $driver): string;
    }
}

?>