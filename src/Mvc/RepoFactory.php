<?php

namespace Slate\Mvc {

    use Slate\Data\Repository\FileSystemEncryptedRepository;
    use Slate\Data\Repository\FileSystemRepository;
    use Slate\IO\SysvSharedMemoryRepository;
    use Slate\Utility\Factory;

    class RepoFactory extends Factory {
        public const MAP = [
            "filesystem"        => FileSystemRepository::class,
            "memory"            => SysvSharedMemoryRepository::class,
            "encrypted"         => EncryptedFileSystemRepository::class
        ];
    }
}

?>