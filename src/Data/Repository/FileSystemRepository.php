<?php

namespace Slate\Data\Repository  {
    use Slate\Data\SerializedRepository;

    /**
     * A cache using the filesystem.
     * 
     * @see Slate\Data\Repository
     */
    class FileSystemRepository extends SerializedRepository {
        use TFileSystemRepository;
    }
}

?>