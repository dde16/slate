<?php declare(strict_types = 1);

namespace Slate\Data {
    trait TIterable {
        /** Iterable */
        public function rewind(): mixed {
            return reset($this->{static::CONTAINER});
        }

        public function current(): mixed {
            return current($this->{static::CONTAINER});
        }

        public function prev(): void {
            prev($this->{static::CONTAINER});
        }

        public function key(): string|int|null {
            return key($this->{static::CONTAINER});
        }

        public function next(): void {
            next($this->{static::CONTAINER});
        }

        public function valid(): bool {
            return key($this->{static::CONTAINER}) !== null;
        }
    }
}

?>