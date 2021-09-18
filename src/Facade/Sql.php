<?php

namespace Slate\Facade {
    use Slate\Utility\Facade;
    use Slate\Data\IStringForwardConvertable;
    use Slate\Sql\Clause\SqlPartitionByClause;
    use Slate\Sql\Condition\SqlCaseCondition;
    use Slate\Sql\SqlExistsExpression;
    use Slate\Sql\SqlWindowFunction;

    final class Sql extends Facade {
        public static function winfn(string $name): SqlWindowFunction {
            return(new SqlWindowFunction($name));
        }

        public static function partitionBy(string|IStringForwardConvertable $expr): SqlPartitionByClause {
            return(new SqlPartitionByClause($expr));
        }

        public static function case(array $whens = []): SqlCaseCondition {
            return(new SqlCaseCondition($whens));
        }

        public static function exists(IStringForwardConvertable $source): SqlExistsExpression {
            return(new SqlExistsExpression($source));
        }

        public static function sqlify(mixed $values, string $delimiter = ",", string $seat = "''", string $wrapper = "()"): string {
            if(!\Any::isArray($values)) {
                $values = [$values];
                $wrapper = "";
            }

            return \Str::wrapc(\Arr::join(
                \Arr::map(
                    $values,
                    function($value) use($seat) {
                        if(is_object($value)) {
                            if(\Cls::hasInterface($value, IStringForwardConvertable::class) || \Cls::hasMethod($value, "toString")) {
                                $value = $value->toString();
                            }
                            else {
                                throw new \Error("Passed object of type " . get_class($value) . " without the toString method");
                            }
                        }
                        else if(is_array($value)) {
                            throw new \Error("Subvalue is array.");
                        }
                        else {
                            $value = ($value !== null) ? \Str::wrapc($value, $seat) : null;
                        }

                        return $value ?: "NULL";
                    }
                ),
                $delimiter
            ), $wrapper);
        }
    }
}

?>