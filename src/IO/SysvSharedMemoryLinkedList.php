<?php

namespace Slate\IO {
    use ArrayAccess;
    use Closure;
    use Countable;
    use Generator;
    use Slate\Data\TOffsetExtended;
    use Slate\Data\Iterator\IExtendedIterator;

    class SysvSharedMemoryLinkedList extends SysvSharedMemory implements ArrayAccess, Countable, IExtendedIterator {
        const VAR_INIT            = 0;
        const VAR_ROWS_COUNT      = 1;
        const VAR_ROWS_START      = 2;
        const VAR_HEAD_POINTER    = 3;
        const VAR_TAIL_POINTER    = 4;
        const VAR_FREE_POINTER    = 5;

        use TOffsetExtended;

        public int    $position = -1;

        /** @see Iterator::rewind() */
        public function rewind(): void {
            $this->position = $this->pull(static::VAR_TAIL_POINTER);
        }

        /** @see Iterator::valid() */
        public function valid(): bool {
            return $this->position !== -1;
        }

        /** @see Iterator::key() */
        public function key(): mixed {
            return $this->position - $this->pull(static::VAR_ROWS_START);
        }

        /** @see Iterator::prev() */
        public function prev(): void {
            $this->position = $this->pull($this->position)[1];
        }

        /** @see Iterator::next() */
        public function next(): void {
            $this->position = $this->pull($this->position)[2] ?: -1;
        }

        public function filter(Closure $fn, int $limit = 0, int $offset = 0): Generator {
            if($limit > 0)
                $limit = $offset + $limit;

            $index = 0;
            foreach($this as $pointer => $row){
                if ($fn($row) && ($index >= $offset && ($limit > 0 ? $index < $limit : true))) {
                    yield [$index, $row];
                }
                $index++;
            }
        }

        public function limit(int $limit, int $offset = 0): Generator {
            $index = 0;
            foreach($this as $pointer => $row) {
                if($index >= $offset && $index < ($offset + $limit)) {
                    yield $row;
                }
                $index++;
            }
        }

        public function where(string $key, $value, int $limit = 0, int $offset = 0, bool $strict = false): Generator {
            return $this->filter(fn($row) => (($strict && $row[$key] === $value) || $row[$key] == $value), $limit, $offset);
        }

        public function first(string $key, $value, bool $strict = false): array|null {
            return $this->where($key, $value, strict: $strict)->current();
        }

        public function last(string $key, $value, bool $strict = false): array|null {
            return \Arr::last(
                iterator_to_array($this->where($key, $value, strict: $strict))
            );
        }

        public function translate($index): int {
            return $this->pull(static::VAR_ROWS_START) + intval($index);
        }

        /** @see Iterator::current() */
        public function current(): mixed {
            return $this->pull($this->position)[0];
        }

        public function acquire(): void {
            parent::acquire();
            
            if(!$this->has(static::VAR_INIT)) {
                $this->initialise();
            }
        }

        public function isFull(): bool {
            return($this->pull(static::VAR_ROWS_COUNT) >= (PHP_INT_MAX - $this->pull(static::VAR_ROWS_START)));
        }

        public function isEmpty(): bool {
            return $this->pull(static::VAR_ROWS_COUNT) === 0;
        }

        /** @see Countable::count() */
        public function count(?Closure $filter = null): int {
            $count = 0;

            if($filter !== null) {
                foreach($this as $index => $row) {
                    if($filter($row, $index)) {
                        $count++;
                    }
                }
            }
            else {
                $count = $this->pull(static::VAR_ROWS_COUNT);
            }

            

            return $count;
        }

        public function nextFree(): int {
            if($this->isFull())
                return -1;

            $index = $this->pull(static::VAR_ROWS_START);

            while($this->has($index) && $index < PHP_INT_MAX) {
                $index++;
            }

            return $index;
        }

        public function offsetAssign($index, $row): void {
            $index = $this->translate($index);

            if($this->has($index)) {
                parent::modify($index, function($element) use($row) {
                    $element[0] = $row;

                    return $element;
                });
            }
            else {
                throw new \Error("The row must exist to modify it.");
            }
        }

        public function update(int $index, string $key, $value): mixed {
            return parent::update($this->translate($index), $key, $value);
        }


        public function offsetPush($row): void {
            if($this->isFull())
                throw new \Error("The table is full.");

            $head = $this->pull(static::VAR_HEAD_POINTER);
            $tail = $this->pull(static::VAR_TAIL_POINTER);

            $nextFree = $this->nextFree();

            if($head !== -1) {
                parent::modify($head, function($row) use($nextFree) {
                    $row[2] = $nextFree;

                    return $row;
                });
            }
            
            if($tail === -1) {
                $this->put(static::VAR_TAIL_POINTER, $nextFree);
            }

            $this->put($nextFree, [$row, $head, -1]);
            $this->put(static::VAR_HEAD_POINTER, $nextFree);

            $this->postIncrement(static::VAR_ROWS_COUNT);
        }

        public function offsetExists($index): bool {
            return $this->has(intval($index));
        }

        public function offsetUnset($index): void {
            $index = $this->translate($index);

            if($this->has($index)) {

                list($currentRow, $currentPrev, $currentNext) = $this->pull($index);

                if($currentPrev !== -1) {
                    parent::modify($currentPrev, function($prev) use($currentNext) {
                        $prev[2] = $currentNext;

                        return $prev;
                    });
                }
                else if($currentNext !== -1) {
                    $this->put(static::VAR_TAIL_POINTER, $currentNext);
                }
                else {
                    $this->put(static::VAR_TAIL_POINTER, -1);
                }

                if($currentNext !== -1) {
                    parent::modify($currentNext, function($next) use($currentPrev) {
                        $next[1] = $currentPrev;

                        return $next;
                    });
                }
                else if($currentPrev !== -1) {
                    $this->put(static::VAR_HEAD_POINTER, $currentPrev);
                }
                else {
                    $this->put(static::VAR_HEAD_POINTER, -1);
                }

                $this->remove($index);
                $this->postDecrement(static::VAR_ROWS_COUNT);
            }
        }

        public function offsetGet($index): mixed {
            $index = $this->translate($index);

            return parent::has($index) ? $this->pull($index)[0] : null;
        }

        public function capacity(): int {
            return (PHP_INT_MAX - $this->pull(static::VAR_ROWS_START));
        }

        public function truncate(): void {
            $prevIndex = -1;

            foreach($this as $nextIndex => $nextRow) {
                if($prevIndex !== -1) unset($this[$prevIndex]);

                $prevIndex = $nextIndex;
            }

            if($prevIndex !== -1) unset($this[$prevIndex]);
        }

        public function initialise(): void {
            $this->put(static::VAR_INIT, true);

            $rowsStart = static::VAR_FREE_POINTER + 1;

            $this->put(static::VAR_ROWS_COUNT,    0);
            $this->put(static::VAR_ROWS_START,    $rowsStart);
            $this->put(static::VAR_HEAD_POINTER,  -1);
            $this->put(static::VAR_TAIL_POINTER,  -1);
            $this->put(static::VAR_FREE_POINTER,  $rowsStart);
        }

    }
}

?>