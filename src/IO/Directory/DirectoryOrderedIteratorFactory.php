<?php

namespace Slate\IO\Directory {

    use Slate\Utility\Factory;

    /**
     * Sorting: one separates items into different kinds or classes
     * Ordering: one arranges the items in a particular order
     */
    class DirectoryOrderedIteratorFactory extends Factory {
        public const MAP = [
            "name" => DirectoryNameOrderedIterator::class,
        ];
    }
}

?>