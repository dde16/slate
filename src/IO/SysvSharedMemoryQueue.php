<?php

namespace Slate\IO {
    use Slate\Data\Structure\IQueue;
    use Slate\Data\Structure\IQueueLimited;

    class SysvSharedMemoryQueue extends SysvSharedMemoryLinkedList implements IQueue, IQueueLimited {
        use TSysvMemoryQueue;

        protected int $allocated = -1;

        /**
         * @see Slate\Data\IQueue::isFull
         * @see Slate\IO\SysvSharedMemoryLinkedList::isFull
         */
        public function isFull(): bool {
            return($this->pull(static::VAR_ROWS_COUNT) === $this->allocated);
        }

        /** @see Slate\Data\IQueueLimited::allocate */
        public function allocate(int $size): void {
            $this->allocated = $size;
        }

        /** @see Slate\Data\IQueueLimited::capacity */
        public function capacity(): int {
            return $this->allocated;
        }
    }
}

?>