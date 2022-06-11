<?php declare(strict_types = 1);
namespace Slate\Sysv {

    use Slate\Sysv\SysvMessageQueue;
    use Slate\Sysv\SysvSharedMemoryHashmap;
    use Slate\Sysv\SysvSharedMemoryLinkedList;
    use Slate\Sysv\SysvSharedMemoryTable;
    use Slate\Utility\Factory;

    class SysvSharedMemoryFactory extends Factory {
        public const MAP = [
            "repository"    => SysvSharedMemoryRepository::class,
            "table-queue"   => SysvSharedMemoryTableQueue::class,
            "queue"         => SysvSharedMemoryQueue::class,
            "table"         => SysvSharedMemoryTable::class,
            "hashmap"       => SysvSharedMemoryHashmap::class,
            "dictionary"    => SysvSharedMemoryDictionary::class,
            "linkedlist"    => SysvSharedMemoryLinkedList::class,
            "message-queue" => SysvMessageQueue::class,
            "semaphore"     => SysvSemaphore::class
        ];
    }
}

?>