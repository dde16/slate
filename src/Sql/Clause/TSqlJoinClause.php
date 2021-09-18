<?php

namespace Slate\Sql\Clause {
    use Slate\Sql\Condition\SqlCondition;

    use Closure;
    use Slate\Data\IStringForwardConvertable;

trait TSqlJoinClause {
        protected array $joins = [];

        public function crossJoin(string|IStringForwardConvertable $reference, Closure $on = null, string $as = null): static {
            return $this->_join("CROSS JOIN", $reference, $on, $as);
        }

        public function join(string|IStringForwardConvertable $reference, Closure $on = null, string $as = null): static {
            return $this->_join("JOIN", $reference, $on, $as);
        }

        public function fullOuterJoin(string|IStringForwardConvertable $reference, Closure $on = null, string $as = null): static {
            return $this->_join("FULL OUTER JOIN", $reference, $on, $as);
        }

        public function innerJoin(string|IStringForwardConvertable $reference, Closure $on = null, string $as = null): static {
            return $this->_join("INNER JOIN", $reference, $on, $as);
        }

        public function leftJoin(string|IStringForwardConvertable $reference, Closure $on = null, string $as = null): static {
            return $this->_join("LEFT JOIN", $reference, $on, $as);
        }

        public function leftOuterJoin(string|IStringForwardConvertable $reference, Closure $on = null, string $as = null): static {
            return $this->_join("LEFT OUTER JOIN", $reference, $on, $as);
        }

        public function rightJoin(string|IStringForwardConvertable $reference, Closure $on = null, string $as = null): static {
            return $this->_join("RIGHT JOIN", $reference, $on, $as);
        }

        public function rightOuterJoin(string|IStringForwardConvertable $reference, Closure $on = null, string $as = null): static {
            return $this->_join("RIGHT OUTER JOIN", $reference, $on, $as);
        }

        public function _join(string $type, string|IStringForwardConvertable $reference, Closure $on = null, string $as = null): static {
            $this->joins[] = [$type, $reference, $as, $on(new SqlCondition())];

            return $this;
        }
        
        public function buildJoinClause(): string {
            return \Arr::join(\Arr::map(
                $this->joins,
                function($join) {
                    return \Arr::join(
                        \Arr::filter([
                            $join[0],
                            is_object($join[1]) ? $join[1]->toString() : $join[1],
                            ($join[2] !== null ? "AS " . $join[2] : null),
                            ($join[3] !== null ? "ON (" . $join[3]->toString() . ")" : null)
                        ]),
                        " "
                    );
                }
            ), " ");
        }
    }
}

?>