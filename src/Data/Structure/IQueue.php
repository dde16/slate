<?php

namespace Slate\Data\Structure {
    interface IQueue {
                
        /**
         * Check whether the queue is full.
         * 
         * @return bool
         */
        function isFull(): bool;
        
        /**
         * Check whether the queue is empty.
         * 
         * @return bool
         */
        function isEmpty(): bool;

        /**
         * Add an item to the queue.
         * 
         * @param mixed $value
         * 
         * @return bool
         */
        function enqueue(mixed $value): bool;

        /**
         * Remove an item from on top of the queue.
         * 
         * @return mixed
         */
        function dequeue(): mixed;
    }
}

?>