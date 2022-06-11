<?php declare(strict_types = 1);

namespace Slate\Sysv\SysvSharedMemoryTable {

    use Closure;
    use Generator;
    use RuntimeException;
    use Slate\Neat\EntityDesign;
    use Slate\Sysv\SysvSharedMemoryDictionary;
    use Slate\Sysv\SysvSharedMemoryTable;

    class SysvSharedMemoryTableWhere {
        public array $conditions;
    
        public function __construct() {
            $this->conditions = [];
        }
    
        protected function _condition(Closure|string $argument): SysvSharedMemoryTableWhere|SysvSharedMemoryTableCondition {
            if($argument instanceof Closure)
                return $argument(new SysvSharedMemoryTableWhere());
    
            return new SysvSharedMemoryTableCondition($this, $argument);
    
        }
    
        public function condition(Closure|string $argument, string $logic) {
            $this->conditions[] = [$logic, $test = $this->_condition($argument)];
    
            return $test;
        }
    
        public function and(Closure|string $argument): SysvSharedMemoryTableWhere|SysvSharedMemoryTableCondition {
            return $this->condition($argument, "AND");
        }
    
        public function or(Closure|string $argument): SysvSharedMemoryTableWhere|SysvSharedMemoryTableCondition {
            return $this->condition($argument, "OR");
        }
    
        public function where(Closure|string $argument): SysvSharedMemoryTableWhere|SysvSharedMemoryTableCondition {
            return $this->andWhere($argument);
        }
    
        public function andWhere(Closure|string $argument): SysvSharedMemoryTableWhere|SysvSharedMemoryTableCondition {
            return $this->condition($argument, "AND");
        }
        
        public function orWhere(Closure|string $argument): SysvSharedMemoryTableWhere|SysvSharedMemoryTableCondition {
            return $this->condition($argument, "OR");
        }
    
        /**
         * Validate the wheres against a row, or without a row; then only validate pretests.
         *
         * @param array|null $row
         *
         * @return boolean
         */
        public function validate(array|object $row = null): bool {
            $valid = true;
    
            foreach($this->conditions as [$logic, $condition]) {
                if(!$condition->isPretested() && $row !== null) {
                    $test = false;
    
                    if($condition instanceof SysvSharedMemoryTableCondition) {
                        $property = $condition->property;
                        $value    = is_object($row) ? $row->{$property} : $row[$property];
    
                        switch($condition->operator) {
                            case SysvSharedMemoryTableOperator::EQUAL:
                                $test = $value === $condition->value;
                                break;
                            case SysvSharedMemoryTableOperator::NOT_EQUAL:
                                $test = $value !== $condition->value;
                                break;
                            case SysvSharedMemoryTableOperator::MATCHES:
                                if(is_string($value))
                                    $test = preg_match($condition->value, $value);
                                break;
                            case SysvSharedMemoryTableOperator::LIKE:
                                if(is_string($value))
                                    $test = fnmatch($condition->value, $value);
                                break;
                            case SysvSharedMemoryTableOperator::LESS_THAN:
                                $test = $value < $condition->value;
                                break;
                            case SysvSharedMemoryTableOperator::LESS_THAN_OR_EQUAL:
                                $test = $value <= $condition->value;
                                break;
                            case SysvSharedMemoryTableOperator::GREATER_THAN:
                                $test = $value > $condition->value;
                                break;
                            case SysvSharedMemoryTableOperator::GREATER_THAN_OR_EQUAL:
                                $test = $value >= $condition->value;
                                break;
                            case SysvSharedMemoryTableOperator::IN:
                                $test = \Arr::contains($condition->value, $value);
                                break;
                        }
                    }
                    else if($condition instanceof SysvSharedMemoryTableWhere) {
                        $test = $condition->validate($row);
                    }
                }
                else if($condition->isPretested()) {
                    $test = $condition->getPretest();
                }
    
                $valid = $logic === "AND" ? ($valid && $test) : ($valid || $test);
            }
    
            return $valid;
        }

        public function voidPretests(): void {
            foreach($this->conditions as [$logic, $condition]) {
                if($condition instanceof SysvSharedMemoryTableCondition) {
                    $condition->voidPretest();
                }
                else if($condition instanceof SysvSharedMemoryTableWhere) {
                    $condition->voidPretests();
                }
            }
        }
    
        /**
         * Build all pretests and return the rows that should be prioritised for filtering.
         *
         * @return Generator
         */
        public function prioritise(array &$pretests, SysvSharedMemoryTable $table, string $entity, EntityDesign $design): Generator {
            foreach($this->conditions as [$logic, $condition]) {
                if($condition instanceof SysvSharedMemoryTableCondition) {
                    $columnProperty = $design->getColumnProperty($condition->property);
                    $column = $columnProperty->getColumn($entity);
    
                    if($columnProperty === null)
                        throw new RuntimeException("Unknown property $entity->{$condition->property}.");
    
                    if(($condition->operator === SysvSharedMemoryTableOperator::EQUAL) || ($isNotEqual = $condition->operator === SysvSharedMemoryTableOperator::NOT_EQUAL)) {
                        if(!$condition->isPretested()) {
                            $pretest = false;
                            
                            if(\Arr::hasKey($pretests, $condition->property)) {
                                $pretest = $pretests[$condition->property];
                            }
                            else {
                                if($column->isKey()) {
                                    /** @var SysvSharedMemoryDictionary $index */
                                    $index = $table->indexes[$columnProperty->getColumnName()];
                                    $pretest = $index->offsetExists($condition->value);
    
                                    if($isNotEqual)
                                        $pretest = !$pretest;
    
                                    $pretests[$condition->property] = $pretest;
                                    $condition->setPretest($pretest);
    
                                    if($pretest)
                                        yield $index[$condition->value];
                                }
                                else if($isNotEqual) {
                                    $counter = $table->counters[$columnProperty->getColumnName()];
                                    $count   = $counter[$condition->value];
    
                                    $pretest = $count === 0;
    
                                    $pretests[$condition->property] = $pretest;
                                    $condition->setPretest($pretest);
                                }
                            }
                        }
                    }
                }
                else {
                    foreach($condition->prioritise($pretests) as $prioritisedPointer) {
                        yield $prioritisedPointer;
                    }
                }
            }
        }
    }
}

?>