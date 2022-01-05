<?php

namespace Slate\Data\Iterator {
    use ArrayIterator;
    use Generator;
    use Slate\Data\Iterator\ArrayExtendedIterator;
    use Slate\Data\Iterator\IExtendedIterator;

    /**
     * A manual associative iterator since we cant access the internal
     * pointer of an associative array for the current key.
     */
    class ArrayAssociativeIterator implements IExtendedIterator  {
        protected array $array;
        protected int   $pointer = 0;

        public function __construct(array $array = []) {
            $this->array = $array;
        }

        public function valid(): bool {
            return ($this->pointer < count($this->array) && $this->pointer > -1);
        }

        public function reverse(): Generator {
            $this->end();

            while($this->valid()) {
                yield [$this->key(), $this->current()];

                $this->prev();
            }
        }

        public function key(): string|int {
            return key($this->array);
        }

        public function prev(): void {
            prev($this->array);
            $this->pointer--;
        }

        public function next(): void {
            next($this->array);
            $this->pointer++;
        }

        public function seek(): void {
            throw new \Error("Cannot seek on an Associative iterator.");
        }

        public function current(): mixed {
            return current($this->array);
        }

        public function end(): void {
            end($this->array);
            $this->pointer = count($this->array)-1;
        }

        public function rewind(): void {
            reset($this->array);
            $this->pointer = 0;
        }

    }
}

?>