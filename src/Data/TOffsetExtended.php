<?php

namespace Slate\Data {
    /**
     * A trait that adds an extra function to make it easier to handle
     * pushing and assigning to array accessible classes.
     */
    trait TOffsetExtended {
        public function offsetSet($offset, $value): void {
            if($offset !== NULL) {
                $this->offsetAssign($offset, $value);
            }
            else {
                $this->offsetPush($value);
            }
        }
    }
}

?>