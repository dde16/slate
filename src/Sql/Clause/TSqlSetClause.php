<?php declare(strict_types = 1);

namespace Slate\Sql\Clause {
    use Slate\Facade\Sql;

    
    trait TSqlSetClause {
        protected array $set = [];

        public function set(string $reference, $equals) {
            $this->set[$reference] = Sql::sqlify($equals);

            return $this;
        }

        public function setMany(array $set) {
            $this->set = array_merge(
                $this->set,
                \Arr::map(
                    $set,
                    function($value) {
                        return Sql::sqlify($value);
                    }
                )
            );

            return $this;
        }

        protected function buildSetClauseArray() {
            return \Arr::join(
                \Arr::map(
                    \Arr::entries($this->set, generator: false),
                    function($entry) {
                        return \Arr::join($entry, "=");
                    }
                ),
                ", "
            );
        }
        
        
        public function buildSetClause() {
            return !\Arr::isEmpty($this->set)
                ? "SET " . $this->buildSetClauseArray()
                : null;
        }
    }
}

?>