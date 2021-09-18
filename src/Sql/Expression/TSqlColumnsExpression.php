<?php

namespace Slate\Sql\Expression {
    use Slate\Sql\SqlReference;

    trait TSqlColumnsExpression {
        protected array $columns = [];
        
        public function buildColumnsExpression() {
            return !\Arr::isEmpty($this->columns) ? \Arr::join(
                \Arr::map(
                    $this->columns,
                    function($column){
                        return $column->toString();
                    }
                ),
                ", "
            ) : "*";
        }

        public function column(string|object $reference, string $as = null): object {
            $column = $this->columns[] = new SqlReference($reference);

            if($as) $column->as($as);
            
            return $this;
        }

        public function columns(array $columns): static {
            foreach($columns as $key => $value) {
                $column = $this->columns[] = new SqlReference($value);

                
                if(\Arr::isAssocOffset($key))
                    $column->as($key);
            }

            return $this;
        }
    }
}

?>