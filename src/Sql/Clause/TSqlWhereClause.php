<?php

namespace Slate\Sql\Clause {
    use Slate\Sql\Carry\SqlCarryCondition;
    use Slate\Sql\Condition\SqlCondition;
    use Slate\Facade\Sql;

    trait TSqlWhereClause {
        public ?SqlCondition $wheres = null;
        
        public function andWhere(): object {
            if($this->wheres === null) $this->wheres = new SqlCondition();

            $this->wheres->where(...func_get_args());

            return $this;
        }
        
        public function where(): object {
            return $this->andWhere(...func_get_args());
        }

        public function orWhere(): object {
            if($this->wheres === null) $this->wheres = new SqlCondition();

            $this->wheres->orWhere(...func_get_args());

            return $this;
        }
        
        
        public function buildWhereClause(): string|null {
            return $this->wheres !== null ? ("WHERE " . \Str::wrapc($this->wheres->toString(), "()")) : null;
        }
    }
}

?>