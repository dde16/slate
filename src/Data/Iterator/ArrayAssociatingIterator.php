<?php

namespace Slate\Data\Iterator {
    use ArrayIterator;

    /**
     * An iterator that can use an unassociated array, along side keys and turn it into 
     * and associative iterator without modifying the original array.
     */
    class ArrayAssociatingIterator extends ArrayIterator {
        protected array $keys;

        public function __construct(array $keys, array &$array) {
            $this->keys = $keys;

            if(empty($keys))
                throw new \Error("There must be at least one key when creating an ArrayAssocIterator.");
            
            parent::__construct($array);
        }

        public function current(): mixed {
            $current = parent::current();

            return is_array($current)
                ? \Arr::key($current, $this->keys)
                : $current;
        }
    }
}

?>