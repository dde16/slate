<?php declare(strict_types = 1);

namespace Slate\Data\Repository {
    use Slate\IO\Directory;
    
    class FileSystemEncryptedRepository extends EncryptedRepository {
        use TFileSystemRepository;

        public function __construct(string $directory, string $method, string $key, bool $autoforget = true) {
            parent::__construct($method, $key, $autoforget);

            $this->directory = new Directory($directory);
            $this->directory->open(create: true);
        }

        public function __destruct() {
            $this->directory->close();
        }
    }
}

?>