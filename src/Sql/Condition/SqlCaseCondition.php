<?php

namespace Slate\Sql\Condition {
    use Slate\Facade\DB;
    use Slate\Facade\Sql;

    class SqlCaseCondition extends SqlCondition {
        protected mixed $else = null;

        public function __construct(array $whens = [], mixed $else = null) {

            foreach($whens as $condition => $then)  {
                $this->when(DB::raw($condition))->then($then);
            }

            $this->else($else);
        }

        public function when() {
            $this->conditions[] = [$this->_condition(func_get_args()), ($then = (new SqlCaseWhen($this)))];

            return $then;
        }

        public function else(mixed $value) {
            $this->else = $value;

            return $this;
        }

        public function toString():string {
            return !\Arr::isEmpty($this->conditions) ? ("CASE " . \Arr::join(
                \Arr::map(
                    $this->conditions,
                    function($condition){
                        return "WHEN " . (is_array($condition[0]) ? \Arr::join($condition[0], " ") : Sql::sqlify($condition[0])) . " " . $condition[1]->toString();
                    }
                ),
                " "
            ) . " ELSE " . Sql::sqlify($this->else) . " END") : Sql::sqlify($this->else);
        }
    }
}

?>