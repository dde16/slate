<?php

namespace Slate\Data {
    trait TIterable {
        /** Iterable */
        public function rewind(): mixed {
            return reset($this->{static::$container});
        }

        public function current(): mixed {
            return current($this->{static::$container});
        }

        public function prev(): void {
            prev($this->{static::$container});
        }

        public function key(): string|int|null {
            return key($this->{static::$container});
        }

        public function next(): void {
            next($this->{static::$container});
        }

        public function valid(): bool {
            return key($this->{static::$container}) !== null;
        }
    }
}

?>