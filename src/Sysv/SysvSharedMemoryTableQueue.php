<?php declare(strict_types = 1);
namespace Slate\Sysv {

use Slate\Data\Structure\IQueue;
    use Slate\Data\Structure\IQueueLimited;

    class SysvSharedMemoryTableQueue extends SysvSharedMemoryTable implements IQueue {
        use TSysvMemoryQueue;
    }
}

?>