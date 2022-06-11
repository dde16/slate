<?php declare(strict_types = 1);

namespace Slate\Data\Structure {
    interface IQueueLimited {
        /**
         * Allocate a given number of spaces within the queue.
         * 
         * @return void
         */
        function allocate(int $size): void;

        /**
         * Return the number of allocated spaces for the queue.
         * 
         * @return int
         */
        function capacity(): int;
    }
}

?>