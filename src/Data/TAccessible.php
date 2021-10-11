<?php

namespace Slate\Data {
    //TODO: replace static::$container with constant
    /**
     * A trait used to provide the code to allow array access.
     */
    trait TAccessible {
        use TOffsetExtended;

        public function offsetAssign(string|int $offset, mixed $value): void {
            $this->{static::$container}[$offset] = $value;
        }

        public function offsetPush(mixed $value): void {
            $this->{static::$container}[] = $value;
        }

        public function offsetExists($offset): bool {
            return $this->{static::$container}[$offset] !== NULL;
        }

        public function offsetUnset($offset): void {
            unset($this->{static::$container}[$offset]);
        }

        public function offsetGet($offset): mixed {
            return @$this->{static::$container}[$offset];
        }
    }
}

?>