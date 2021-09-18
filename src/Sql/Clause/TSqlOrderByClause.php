<?php

namespace Slate\Sql\Clause {

use Slate\Data\IStringForwardConvertable;

trait TSqlOrderByClause {
        public array $orderBy = [];
        public ?string $orderDirection = null;

        public function orderBy(string|IStringForwardConvertable ...$references): static {
            foreach($references as $index => $reference) {
                if(is_object($reference)) {
                    assert(
                        \Cls::implements($reference, IStringForwardConvertable::class),
                        new \Error(\Str::format(
                            "Order By reference of class {} at index {}, must implement the {} interface to be convertable to a string.",
                            \Cls::getName($reference),
                            $index,
                            \Cls::getName(IStringForwardConvertable::class)
                        ))
                    );
                }

                $this->orderBy[] = $reference;
            }

            return $this;
        }

        public function orderByAsc(string|IStringForwardConvertable ...$references): static {
            $this->orderDirection = "ASC";

            return $this->orderBy(...$references);
        }

        public function orderByDesc(string|IStringForwardConvertable ...$references): static {
            $this->orderDirection = "DESC";

            return $this->orderBy(...$references);
        }
        
        public function buildOrderByClause(): ?string {
            return !\Arr::isEmpty($this->orderBy)
                ? "ORDER BY " . \Arr::join(
                    \Arr::map(
                        $this->orderBy,
                        function($orderByReference) {
                            return is_object($orderByReference) ? $orderByReference->toString() : $orderByReference;
                        }
                    ), ", ") . (($this->orderDirection ? " $this->orderDirection" : ""))
                : null;
        }
    }
}

?>