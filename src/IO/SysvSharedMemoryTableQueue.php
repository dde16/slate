<?php

namespace Slate\IO {

use Slate\Data\Structure\IQueue;
    use Slate\Data\Structure\IQueueLimited;

    class SysvSharedMemoryTableQueue extends SysvSharedMemoryTable implements IQueue {
        use TSysvMemoryQueue;
    }
}

?>