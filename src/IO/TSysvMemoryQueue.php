<?php

namespace Slate\IO {
    trait TSysvMemoryQueue {
        public function enqueue(mixed $value): bool {
            if($ok = !$this->isFull()) {
                $this[] = $value;
            }

            return $ok;
        }

        public function dequeue(): mixed {
            if(!$this->isEmpty()) {
                $index = $this->pull(static::VAR_HEAD_POINTER) - $this->pull(static::VAR_ROWS_START);
                $value =  $this[$index];

                unset($this[$index]);

                return $value;
            }

            return false;
        }
    }
}

?>