<?php declare(strict_types = 1);

namespace Slate\Data\Structure {

    use RuntimeException;
    use Slate\Data\BasicArray;
    use Slate\Data\Structure\IQueue;
    use Slate\Data\Structure\IQueueLimited;

    class Deque extends BasicArray implements IQueue, IQueueLimited {
        protected int   $head;
        protected int   $tail;
        protected int   $capacity;
        protected int   $count;

        public function __construct(int $capacity = -1) {
            parent::__construct([]);

            $this->items    = [];
            $this->head     = -1;
            $this->tail     = -1;
            $this->count    = 0;
            $this->capacity = $capacity;
        }

        public function isFull(): bool {
            return $this->count === $this->capacity();
        }

        public function allocate(int $size): void {
            $this->capacity = $size;
        }

        public function isEmpty(): bool {
            return $this->count === 0;
        }

        public function capacity(): int {
            return ($this->capacity === -1 ? \Integer::MAX : $this->capacity)-1;
        }

        public function assertNotFull(): void {
            if($this->isFull())
                throw new RuntimeException("The Deque is full.");
        }

        public function assertNotEmpty(): void {
            if($this->isEmpty())
                throw new RuntimeException("The Deque is empty.");
        }

        public function dequeue(): mixed {
            $this->assertNotEmpty();

            $item = $this->items[$this->head];

            unset($this->items[$this->head++]);

            $this->count--;

            return $item;

        }

        public function enqueue(mixed $value): bool {
            $this->assertNotFull();

            $this->items[$this->head = \Math::overflow($this->head-1, [0, $this->capacity()])] = $value;
            
            $this->count++;

            return true;
        }
        
        public function push(mixed $value): void {
            $this->offsetPush($value);
        }

        public function pop(): mixed {
            $this->assertNotEmpty();

            $item = $this->items[$this->tail];

            unset($this->items[$this->tail--]);

            $this->count--;

            return $item;
        }

        public function offsetPush(mixed $value): void {
            $this->assertNotFull();
            $this->items[$this->tail = \Math::overflow($this->tail+1, [0, $this->capacity()])] = $value;
            $this->count++;
        }

        public function offsetAssign($offset, $value): void {
            throw new \RuntimeException("You are not allowed to set values on a Deque.");
        }
    }
}

?>