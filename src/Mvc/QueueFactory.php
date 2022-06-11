<?php declare(strict_types = 1);

namespace Slate\Mvc {

    use Slate\Sysv\SysvSharedMemoryQueue;
    use Slate\Sysv\SysvSharedMemoryTable;
    use Slate\Sysv\SysvSharedMemoryTableQueue;
    use Slate\Utility\Factory;

    class QueueFactory extends Factory {
        public const MAP = [
            "persistent-memory-table" => SysvSharedMemoryTableQueue::class,
            "memory"                  => SysvSharedMemoryQueue::class
        ];
    }
}


?>