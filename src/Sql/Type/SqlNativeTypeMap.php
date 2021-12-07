<?php

namespace Slate\Sql\Type {
    use Slate\Utility\TUninstantiable;

    final class SqlNativeTypeMap {
        use TUninstantiable;

        public const MAP = [
            "*" => [
                "string"         => "varchar(255)",
                "int"            => "int",
                "float"          => "float",
                "bool"           => "tinyint(1) unsigned",
                "object"         => "json",
                "array"          => "json",
                \DateTime::class => "datetime"
            ]
        ];
    }
}

?>