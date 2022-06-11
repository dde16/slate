<?php declare(strict_types = 1);

namespace Slate\Sql\Operator\Contract {
    use Slate\Sql\Contract\ISqlable;
    use Slate\Sql\Operator\SqlUnionOperator;

    interface ISqlUnionable {
        public static function union(array $statements, string $type): SqlUnionOperator;
        public static function unionAll(ISqlable ...$statements): SqlUnionOperator;
        public static function unionDistinct(ISqlable ...$statements): SqlUnionOperator;
    }
}

?>