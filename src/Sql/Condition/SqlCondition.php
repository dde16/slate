<?php declare(strict_types = 1);

namespace Slate\Sql\Condition {

    use Closure;
    use Slate\Facade\Sql;
    use Slate\Sql\Contract\ISqlable;

    class SqlCondition implements ISqlable {
        protected array $conditions = [];

        protected function _condition(array $arguments) {
            $typemap = \Arr::padRight(
                \Arr::map(
                    $arguments,
                    function($value) {
                        return is_scalar($value) ? "scalar" : \Any::getType($value);
                    }
                ),
                "null",
                3
            );

            switch($typemap) {
                case ["object", "null", "null"]:
                    return $arguments[0] instanceof Closure ? $arguments[0](new SqlCondition()) : $arguments[0];
                    break;
                case ["scalar", "scalar", "scalar"]:
                case ["scalar", "scalar", "array"]:
                case ["object", "scalar", "array"]:
                case ["scalar", "scalar", "object"]:
                case ["object", "scalar", "scalar"]:
                case ["object", "scalar", "object"]:
                    list($reference, $operator, $value) = $arguments;
                    break;
                case ["scalar", "object", "null"]:
                case ["object", "object", "null"]:
                case ["object", "scalar", "null"]:
                case ["scalar", "scalar", "null"]:
                    $operator = "=";
                    list($reference, $value) = $arguments;
                    break;
                default:
                    throw new \Error(\Str::repr($typemap));
                    break;
            }

            return [$reference, $operator, Sql::sqlify($value)];
        }

        public function condition(array $arguments, string $logic) {
            $this->conditions[] = [$logic, $this->_condition($arguments)];

            return $this;
        }

        public function and() {
            return $this->condition(func_get_args(), "AND");
        }

        public function or() {
            return $this->condition(func_get_args(), "OR");
        }

        public function where() {
            return $this->condition(func_get_args(), "AND");
        }

        public function andWhere() {
            return $this->condition(func_get_args(), "AND");
        }
        
        public function orWhere() {
            return $this->condition(func_get_args(), "OR");
        }

        public function on() {
            return $this->condition(func_get_args(), "AND");
        }

        public function orOn() {
            return $this->condition(func_get_args(), "OR");
        }

        public function buildSql(): ?array
        {
            return [];
        }

        public function toSql(): ?string {
            return !\Arr::isEmpty($this->conditions) ? \Arr::join(
                \Arr::slice(
                    \Arr::flatten(
                        \Arr::map(
                            $this->conditions,
                            function($condition){
                                if(is_object($condition[0])) {
                                    $condition[0] = "(".$condition[0]->toSql().")";
                                }

                                if(is_object($condition[1])) {
                                    $condition[1] = "(".$condition[1]->toSql().")";
                                }
                                else if(is_array($condition[1])) {
                                    $condition[1] = \Arr::join(
                                        \Arr::map(
                                            $condition[1],
                                            function($condition) {
                                                return is_object($condition) ? $condition->toSql() : $condition;
                                            }
                                        ),
                                        " "
                                    );
                                }

                                return $condition;
                            }
                        )
                    ),
                    1
                ),
                " "
            ) : null;
        }
    }
}

?>