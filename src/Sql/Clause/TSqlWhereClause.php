<?php

namespace Slate\Sql\Clause {
    use Slate\Sql\Carry\SqlCarryCondition;
    use Slate\Sql\Condition\SqlCondition;
    use Slate\Facade\Sql;

    trait TSqlWhereClause {
        public ?SqlCondition $wheres = null;
        
        public function where(): object {
            if($this->wheres === null) $this->wheres = new SqlCondition();

            $this->wheres->where(...func_get_args());

            return $this;
        }

        public function orWhere(): object {
            if($this->wheres === null) $this->wheres = new SqlCondition();

            $this->wheres->orWhere(...func_get_args());

            return $this;
        }
        
        
        public function buildWhereClause(): string|null {
            return (($wheres = $this->wheres) !== null) ?  "WHERE (" . $wheres->toString() . ")" : null;
        }
    }
}

?>