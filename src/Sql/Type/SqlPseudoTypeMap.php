<?php declare(strict_types = 1);

namespace Slate\Sql\Type {

    use Slate\Utility\TUninstantiable;

    final class SqlPsuedoTypeMap {
        use TUninstantiable;

        public const MAP = [
            "*" => [
                "coordinate" => "decimal(11, 8)"
            ]
        ];
    }
}

?>