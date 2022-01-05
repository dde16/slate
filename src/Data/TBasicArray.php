<?php

namespace Slate\Data {
    /**
     * A trait to provide the code to make a class fully compatible with 
     * array operations.
     */
    trait TBasicArray {
        use TAccessible;
        use TCountable;
        use TIterable;

        /** ArrayConvertable */
        public function toArray(): array {
            return $this->{static::CONTAINER};
        }
    }
}

?>