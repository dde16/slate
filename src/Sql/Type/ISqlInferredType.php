<?php

namespace Slate\Sql {
    interface ISqlInferredType {
        static function inferSqlType(string $driver): string;
    }
}

?>