<?php declare(strict_types = 1);

namespace Slate\Facade {
    use Slate\Utility\Facade;
    use Slate\Data\Contract\IStringForwardConvertable;
    use Slate\Sql\Clause\SqlPartitionByClause;
    use Slate\Sql\Condition\SqlCaseCondition;
    use Slate\Sql\Contract\ISqlable;
    use Slate\Sql\Expression\SqlExistsExpression;
    use Slate\Sql\SqlWindowFunction;
    
    use Slate\Sql\Operator\Contract\ISqlUnionable;
    use Slate\Sql\Operator\Trait\TSqlUnionOperator;


    /**
     * A facade containing functions relating to the SQL language.
     */
    final class Sql extends Facade implements ISqlUnionable {
        use TSqlUnionOperator;

        public static function winfn(string $name): SqlWindowFunction {
            return(new SqlWindowFunction($name));
        }

        public static function partitionBy(string|IStringForwardConvertable|ISqlable $expr): SqlPartitionByClause {
            return(new SqlPartitionByClause($expr));
        }

        public static function case(array $whens = []): SqlCaseCondition {
            return(new SqlCaseCondition($whens));
        }

        public static function exists(IStringForwardConvertable|ISqlable $source): SqlExistsExpression {
            return(new SqlExistsExpression($source));
        }

        public static function sqlify(mixed $values, string $delimiter = ",", string $seat = "''", string $wrapper = "()"): string {
            if(!is_array($values)) {
                $values = [$values];
                $wrapper = "";
            }
            
            return \Str::wrapc(\Arr::join(
                \Arr::map(
                    $values,
                    function($value) use($seat) {
                        if(is_object($value)) {
                            if($value instanceof IStringForwardConvertable || \Cls::hasMethod($value, "toString")) {
                                $value = $value->toString();
                            }
                            else if($value instanceof ISqlable) {
                                $value = \Str::wrapc($value->toSql(), "()");
                            }
                            else {
                                throw new \Error("Passed object of type " . get_class($value) . " without the toString method");
                            }
                        }
                        else if(is_array($value)) {
                            throw new \Error("Subvalue is array.");
                        }
                        else {
                            $value = ($value !== null) ? \Str::wrapc(strval($value), $seat) : null;
                        }

                        return $value ?? "NULL";
                    }
                ),
                $delimiter
            ), $wrapper);
        }
    }
}

?>