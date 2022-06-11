<?php declare(strict_types = 1);

namespace Slate\Sql\Operator\Trait {

    use Slate\Sql\Contract\ISqlable;
    use Slate\Sql\Operator\SqlUnionOperator;

    trait TSqlUnionOperator {
        public static function union(array $statements, string $type): SqlUnionOperator {
            return (new SqlUnionOperator($statements, $type));
        }

        public static function unionAll(ISqlable ...$statements): SqlUnionOperator {
            return static::union($statements, "ALL");
        }

        public static function unionDistinct(ISqlable ...$statements): SqlUnionOperator {
            return static::union($statements, "DISTINCT");
        }
    }
}

?>