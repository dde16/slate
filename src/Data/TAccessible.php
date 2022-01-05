<?php

namespace Slate\Data {
    //TODO: replace static::$container with constant
    /**
     * A trait used to provide the code to allow array access.
     */
    trait TAccessible {
        use TOffsetExtended;

        public function offsetAssign(string|int $offset, mixed $value): void {
            $this->{static::CONTAINER}[$offset] = $value;
        }

        public function offsetPush(mixed $value): void {
            $this->{static::CONTAINER}[] = $value;
        }

        public function offsetExists($offset): bool {
            return $this->{static::CONTAINER}[$offset] !== NULL;
        }

        public function offsetUnset($offset): void {
            unset($this->{static::CONTAINER}[$offset]);
        }

        public function offsetGet($offset): mixed {
            return @$this->{static::CONTAINER}[$offset];
        }
    }
}

?>