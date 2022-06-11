<?php declare(strict_types = 1);

namespace Slate\Data {
    trait TCountable {
        /** Countable */
        public function count(): int {
            return count($this->{static::CONTAINER});
        }
    }
}

?>