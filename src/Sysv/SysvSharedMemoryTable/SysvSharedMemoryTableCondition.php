<?php declare(strict_types = 1);

namespace Slate\Sysv\SysvSharedMemoryTable {

    use RuntimeException;

class SysvSharedMemoryTableCondition {
        protected SysvSharedMemoryTableWhere $parent;
    
        public string $property;
        public int    $operator;
        public mixed  $value;
    
        /**
         * Flag whether this test has already been ran.
         *
         * @var boolean
         */
        protected bool   $pretested = false;
    
        /**
         * This sets the value for the pretest that has ran.
         * 
         * A pretest is used for when there are some conditions that will be ran before 
         * the others, such as unique columns or equals columns checking for value counts.
         *
         * @var boolean
         */
        protected bool   $pretest   = false;
    
        public function __construct(SysvSharedMemoryTableWhere $parent, string $property) {   
            $this->parent = $parent;
            $this->property = $property;
            $this->voidPretest();
        }
    
        public function setPretest(bool $pretest): void {
            if($this->isPretested())
                throw new RuntimeException("This condition already has its pretest set.");
    
            $this->pretested = true;
            $this->pretest = $pretest;
        }
    
        public function voidPretest(): void {
            $this->pretested = false;
            $this->pretest = false;
        }
    
        public function isPretested(): bool {
            return $this->pretested;
        }
    
        public function getPretest(): bool {
            $pretest =  $this->pretest;
    
            return $pretest;
        }
    
        public function in(mixed $value): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::IN, $value);
        }
    
        public function eq(mixed $value): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::EQUAL, $value);
        }
        
        public function neq(mixed $value): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::NOT_EQUAL, $value);
        }
    
        public function contains(string $value): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::LIKE, "*{$value}*");
        }
    
        public function startswith(string $value): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::LIKE, "$value*");
        }
    
        public function endswith(string $value): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::LIKE, "*$value");
        }
    
        public function matches(string $pattern): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::MATCHES, $pattern);
        }
    
        public function like(string $pattern): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::LIKE, $pattern);
        }
        
        public function lt(int $value): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::LESS_THAN, $value);
        }
    
        public function lte(int $value): SysvSharedMemoryTableWhere {
            return $this->op(SysvSharedMemoryTableOperator::LESS_THAN_OR_EQUAL, $value);
        }
    
        public function gt(int $value): SysvSharedMemoryTableWhere  {
            return $this->op(SysvSharedMemoryTableOperator::GREATER_THAN, $value);
        }
    
        public function gte(int $value): SysvSharedMemoryTableWhere  {
            return $this->op(SysvSharedMemoryTableOperator::GREATER_THAN_OR_EQUAL, $value);
        }
    
        public function op(int $operator, mixed $value): SysvSharedMemoryTableWhere {
            $this->operator = $operator;
            $this->value = $value;
    
            return $this->parent;
        }
    }
}

?>