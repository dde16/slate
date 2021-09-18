<?php

namespace Slate\Sql\Clause {
    use Slate\Data\IStringForwardConvertable;

    trait TSqlGroupByClause {
        protected array $groupBy = [];

        public function groupBy(string|IStringForwardConvertable ...$references): static {
            foreach($references as $index => $reference) {
                if(is_object($reference)) {
                    assert(
                        \Cls::implements($reference, IStringForwardConvertable::class),
                        new \Error(\Str::format(
                            "Group By reference of class {} at index {}, must implement the {} interface to be convertable to a string.",
                            \Cls::getName($reference),
                            $index,
                            \Cls::getName(IStringForwardConvertable::class)
                        ))
                    );
                }

                $this->groupBy[] = $reference;
            }

            return $this;
        }
        
        public function buildGroupByClause(): string|null {
            return !\Arr::isEmpty($this->groupBy)
                ? "GROUP BY " . \Arr::join(
                    \Arr::map(
                        $this->groupBy,
                        function($groupByReference) {
                            return is_object($groupByReference) ? $groupByReference->toString() : $groupByReference;
                        }
                    ),
                    ", "
                )
                : null;
        }
    }
}

?>