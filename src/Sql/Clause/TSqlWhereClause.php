<?php

namespace Slate\Sql\Clause {
    use Slate\Sql\Carry\SqlCarryCondition;
    use Slate\Sql\Condition\SqlCondition;
    use Slate\Facade\Sql;

    trait TSqlWhereClause {
        public ?SqlCondition $wheres = null;
        public array         $trailingWheres = [];

        
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
            if(($wheres = $this->wheres) !== null) {
                $wheres = clone $wheres;
            }

            if(!\Arr::isEmpty($this->trailingWheres)) {
                if($wheres === null) {
                    $wheres = new SqlCondition;
                }

                foreach($this->trailingWheres as $entry) {
                    list($type, $where) = $entry;
                    
                    $wheres->{$type."Where"}(...$where);
                }

                return "WHERE " . \Str::wrapc($wheres->toString(), "()");
            }
            else if($wheres !== null) {
                return "WHERE " . \Str::wrapc($wheres->toString(), "()");
            }

            return null;
        }
    }
}

?>