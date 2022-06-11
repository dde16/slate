<?php

namespace Slate\Data\Structure {

    use Slate\Data\BasicArray;

    class AdjacencyList extends BasicArray {
        public array $referenced = [];
        public array $referencing = [];
    
        public function getRootKeys(): array {
            return \Arr::keys(\Arr::filter($this->referenced, \Fnc::equals(0)));
        }
    
        protected function createCounts(string|int $key): void {
            if (!\Arr::hasKey($this->referencing, $key)) {
                $this->referencing[$key] = 0;
            }
    
            if (!\Arr::hasKey($this->referenced, $key)) {
                $this->referenced[$key] = 0;
            }
        }
    
        public function fromArray(array $array): void {
            foreach($array as $fromKey => $toKeys) {
                $toKeys = \Arr::always($toKeys);
    
                $this->createCounts($fromKey);
    
                $this->referencing[$fromKey] += count($toKeys);
    
                foreach($toKeys as $toKey) {
                    $this->createCounts($toKey);
    
                    $this->referenced[$toKey]++;
                }
            }
    
            $this->items = $array;
        }
    }
}

?>