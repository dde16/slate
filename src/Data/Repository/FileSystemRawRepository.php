<?php declare(strict_types = 1);

namespace Slate\Data\Repository {
    use Slate\Data\Repository;
    use Slate\IO\Directory;

    class FileSystemRawRepository extends Repository {
        // use TFileSystemRepository {
        //     flush as _flush;
        //     has as _has;
        //     derive as _derive;
        // }

        protected Directory $directory;
        protected string $alg;

        public function __construct(
            string $directory,
            ?string $alg = "sha256"
        ) {
            $this->directory = new Directory($directory);
            $this->directory->open(create: true);

            $this->alg = $alg;
        }
        
        public function has(string $key): bool {
            return $this->directory->hasFile($this->derive($key));
        }

        public function forever(string $key, mixed $value): void {
            $key = $this->derive($key);
            $file = $this->directory->openFile($key);
            $file->open("w");
            $file->write($value);
            $file->flush();
            $file->close();
        }
        
        public function flush(): bool {
            if($this->directory->delete()) {
                $this->directory->open(create: true);
    
                return true;
            }
    
            return false;
        }

        public function forget(string $key): bool {
            return $this->directory->deleteFile($this->derive($key));
        }

        public function pull(string $key, mixed $default = null): mixed {
            $derived = $this->derive($key);
            $value   = null;

            if($this->directory->hasFile($derived)) {
                $file = $this->directory->openFile($derived);
                $file->open("r");
                $value = $file->readAll();
                $file->close();
            }

            return $value;
        }
        
        public function derive(string $key): string {
            return $this->alg !== null ? hash($this->alg, $key) : $key;
        }
    }
}

?>