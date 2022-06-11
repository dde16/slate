<?php declare(strict_types = 1);

namespace Slate\Mvc {

    use Slate\Data\Repository\FileSystemEncryptedRepository;
    use Slate\Data\Repository\FileSystemRepository;
    use Slate\Sysv\SysvSharedMemoryRepository;
    use Slate\Utility\Factory;

    class RepoFactory extends Factory {
        public const MAP = [
            "filesystem"        => FileSystemRepository::class,
            "memory"            => SysvSharedMemoryRepository::class,
            "encrypted-fs"      => EncryptedFileSystemRepository::class
        ];
    }
}

?>