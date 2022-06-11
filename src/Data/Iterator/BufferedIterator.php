<?php declare(strict_types = 1);

namespace Slate\Data\Iterator {
    use Generator;
    use Countable;
    use Iterator;
    use Slate\Data\Contract\IArrayForwardConvertable;
    use Slate\Data\Iterator\IExtendedIterator;

    /**
     * A class that is a Generator except, it can store traversable buffer results until cleared. 
     * This is useful for applications that require backtracking, such as the Interpreter module.
     */
    final class BufferedIterator implements Countable, IExtendedIterator, IArrayForwardConvertable {
        /**
         * Buffer result storage.
         *
         * @var array
         */
        protected array              $buffer;

        /**
         * A pointer for the buffer results.
         * 
         * @var int
         */
        protected int                $pointer;

        /**
         * The generator to source values from.
         * 
         * @var Generator|Iterator
         */
        protected Generator|Iterator $generator;

            
        public function __construct(Generator|Iterator $generator) {
            $this->generator    = $generator;
            $this->pointer      = 0;
            $this->buffer       = [];

            if($this->generator->valid())
                $this->buffer[] = $this->generator->current();
        }

        /**
         * Clear any buffer results.
         *
         * @return void
         */
        public function clear(): void {
            $count = count($this->buffer);
            
            /**
             * Since the index keys will be maintained, we need the
             * end item pointer to calculate what elements to remove.
             * If there is no last key, it has already been cleared.
             */
            $lastKey = \Arr::lastKey($this->buffer);

            if($lastKey !== null) {
                $lastKey = intval($lastKey);

                for($index = 1; $index < $count; $index++) {
                    unset($this->buffer[$lastKey - $index]);
                }
            }
        }

        public function source(): Generator|Iterator {
            return $this->generator;
        }

        /** @see Countable::count() */
        public function count(): int {
            return count($this->buffer);
        }
    
        /** @see Iterator::rewind() */
        public function rewind(): void {
            $this->pointer = 0;
        }
    
        /** @see Iterator::current() */
        public function &current(): mixed {
            return $this->valid() ? $this->buffer[$this->pointer] : null;
        }
    
        /** @see Iterator::prev() */
        public function prev(): void {
            --$this->pointer;
        }
    
        /** @see Iterator::key() */
        public function key(): int {
            return $this->pointer;
        }

        /** @see Iterator::valid() */
        public function valid(): bool {
            return $this->generator->valid() ?: $this->pointer < $this->count()-1;
        }
    
        /** @see Iterator::next() */
        public function next(): void {
            if($this->pointer+1 >= count($this->buffer)) {
                $this->generator->next();

                if($this->generator->valid()) {
                    $this->buffer[] = $this->generator->current();
                    $this->pointer++;
                }
            }
        }

        public function toArray(): array {
            return $this->buffer;
        }
    }
}

?>