<?php

namespace Slate\Data {
    use ArrayAccess;
    use Countable;
    use Slate\Data\Iterator\IExtendedIterator;

    /**
     * A base for classes such as the Collection to be used as an easy way
     * to make a class accessible as an array.
     */
    class BasicArray implements IArrayAccess, IExtendedIterator, Countable {
        public const CONTAINER = "items";

        use TBasicArray;

        public function __construct(array $items = []) {
            $this->{static::CONTAINER} = $items;
        }
    }
}

?>