<?php

namespace Slate\Mvc {

    use Slate\IO\SysvSharedMemoryQueue;
    use Slate\IO\SysvSharedMemoryTable;
    use Slate\IO\SysvSharedMemoryTableQueue;
    use Slate\Utility\Factory;

    class QueueFactory extends Factory {
        public const MAP = [
            "persistent-memory-table" => SysvSharedMemoryTableQueue::class,
            "memory"                  => SysvSharedMemoryQueue::class
        ];
    }
}


?>