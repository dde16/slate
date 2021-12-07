<?php

namespace App\Auxiliary {

    class Deque {
        protected array $items = [];
        protected int   $tail  = 0;
        protected int   $head  = -1;
        protected int   $count = 0;

        public function isEmpty(): bool {
            return $this->count === 0;
        }

        public function count(): int {
            return $this->count;
        }

        public function enqueue(mixed $item): void {
            $this->count++;
            $this->items[$this->head--] = $item;
        }

        public function push(mixed $item): void {
            $this->count++;
            $this->items[$this->tail++] = $item;
        }

        public function pop(): mixed {
            $this->count--;
            $value = $this->items[$this->tail - 1];
            unset($this->items[--$this->tail]);
            return $value;
        }

        public function dequeue(): mixed {
            $this->count--;
            $value = $this->items[$this->head + 1];
            unset($this->items[++$this->head]);
            return $value;
        }

        public function toArray(): array {
            return $this->items;
        }
    }
}

?>