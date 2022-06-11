<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {

    use Closure;
    use Slate\Facade\DB;
    use Slate\Sql\Condition\SqlBlockCondition;
    use Slate\Sql\Condition\SqlColumnCondition;
    use Slate\Sql\Condition\SqlRawCondition;
    use Slate\Structure\TProceduralArray;

    trait TSqlWhereInlineClause {
        use TProceduralArray;

        public SqlBlockCondition $wheres;

        public function registerWheres(): void {
            $this->wheres = new SqlBlockCondition(["logical" => "AND"]);
            $this->refs = [&$this->wheres->children];
        }
    
        protected function transformProceduralArrayCallableToCollection(callable $current): mixed {
            return $current;
        }
        
        public function referenceProceduralArrayValue(array|object &$ref, string|int $key): void {
            $this->refs[] = &$ref[$key]->children;
        }
    
        public function andWhere(object|string $arg0, mixed $arg1 = null, mixed $arg2 = null): static {
            return $this->condition("AND", $arg0, $arg1, $arg2);
        }
    
        public function andWhereNot(object|string $arg0, mixed $arg1 = null, mixed $arg2 = null): static {
            return $this->condition("AND NOT", $arg0, $arg1, $arg2);
        }
    
        public function orWhere(object|string $arg0, mixed $arg1 = null, mixed $arg2 = null): static {
            return $this->condition("OR", $arg0, $arg1, $arg2);
        }
    
        public function orWhereNot(object|string $arg0, mixed $arg1 = null, mixed $arg2 = null): static {
            return $this->condition("OR NOT", $arg0, $arg1, $arg2);
        }
    
        public function where(object|string $arg0, mixed $arg1 = null, mixed $arg2 = null): static {
            return $this->andWhere($arg0, $arg1, $arg2);
        }
    
        public function whereNot(object|string $arg0, mixed $arg1 = null, mixed $arg2 = null): static {
            return $this->andWhereNot($arg0, $arg1, $arg2);
        }

        public function whereNotNull(string $column): static {
            return $this->whereNot(DB::raw("ISNULL($column)"));
        }
    
        public function whereNull(string $column): static {
            return $this->where(DB::raw("ISNULL($column)"));
        }

        public function deriveCondition(string $logicalOperator, object|string $arg0, mixed $arg1 = null, mixed $arg2 = null) {
            $condition = null;

            if($arg0 instanceof Closure) {
                $condition = (new SqlBlockCondition([
                    "logical"  => $logicalOperator,
                    "closure" => $arg0,
                    "children" => []
                ]));
            }
            else if($arg0 !== null && $arg1 !== null) {
                $operator = "=";
                $value    = null;
                
                if($arg2 === null) {
                    $value = $arg1;
                }
                else {
                    $operator = $arg1;
                    $value    = $arg2;
                }
    
                $condition = (new SqlColumnCondition([
                    "logical"  => $logicalOperator,
                    "column"   => $arg0,
                    "operator" => $operator,
                    "value"    => $value
                ]));
            }
            else {
                $condition = (new SqlRawCondition([
                    "logical" => $logicalOperator,
                    "value" => $arg0
                ]));
            }

            return $condition;
        }
    
        public function condition(string $logicalOperator, object|string $arg0, mixed $arg1 = null, mixed $arg2 = null): static {
            $this->pushProceduralArrayValue($this->deriveCondition($logicalOperator, $arg0, $arg1, $arg2));

            return $this;
        }
    
        public function buildWhereClause(): ?string {
            $built = $this->wheres->buildSql();
    
            return $built ? ("WHERE " . $built[1]) : null;
        }
    }
}

?>