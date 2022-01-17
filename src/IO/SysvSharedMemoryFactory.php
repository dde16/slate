<?php

namespace Slate\IO {

    use Slate\IO\SysvMessageQueue;
    use Slate\IO\SysvSharedMemoryHashmap;
    use Slate\IO\SysvSharedMemoryLinkedList;
    use Slate\IO\SysvSharedMemoryTable;
    use Slate\Utility\Factory;

    class SysvSharedMemoryFactory extends Factory {
        public const MAP = [
            "repository"    => SysvSharedMemoryRepository::class,
            "table-queue"   => SysvSharedMemoryTableQueue::class,
            "queue"         => SysvSharedMemoryQueue::class,
            "table"         => SysvSharedMemoryTable::class,
            "hashmap"       => SysvSharedMemoryHashmap::class,
            "linkedlist"    => SysvSharedMemoryLinkedList::class,
            "message-queue" => SysvMessageQueue::class,
        ];
    }
}

?>