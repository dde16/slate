<?php declare(strict_types = 1);

namespace Slate\Sql\Statement {
    trait TSqlSelectStatementPaginate {
        public function _page(int $size, int $number): static {
            $query = clone $this;
    
            $query->limit(
                $size+1,
                ($size * $number) + intval($number > 0)
            );
    
            return $query;
        }
    
        public function page(int $size, int $number): array {
            $query = $this->_page($size, $number);
    
            $rows = $query->all();
    
            list($primaryChunk, $secondaryChunk) = \Arr::padRight(\Arr::chunk($rows, $size), [], 2);
    
            $hasNext = !\Arr::isEmpty($secondaryChunk);
    
            return [$primaryChunk, $hasNext, $query];
        }

    }
}

?>