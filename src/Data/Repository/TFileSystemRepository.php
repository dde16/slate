<?php

namespace Slate\Data\Repository {

    use Closure;
    use Slate\IO\Directory;

    use DateTimeInterface;
    use DateInterval;
    use Slate\IO\File;

    trait TFileSystemRepository {
        /** 
         * The directory where the Cache will be situated.
         * 
         * @see Slate\IO\Directory
         */
        protected Directory $directory;
    
        /**
         * @param string $directory Path to the directory.
         */
        public function __construct(string $directory, string $serializer = "json", bool $autoforget = true) {
            parent::__construct($serializer, $autoforget);

            $this->directory = new Directory($directory);
            $this->directory->open(create: true);

        }
    
        public function flush(): bool {
            // $wasOpen = $this->isOpen();

            if($this->directory->delete()) {
                $this->directory->open(create: true);
    
                return true;
            }
    
            return false;
        }
    
        public function has(string $key): bool {
            return $this->directory->hasFile($this->derive($key));
        }

        public function until(string $key, mixed $value, DateTimeInterface|DateInterval|float|int $ttl = null): void {
            $ttl = $ttl !== null ? \Real::fromDateTime($ttl) : -1.0;

            $key = $this->derive($key);
    
            $value = $this->serialize($value);

            $file = $this->directory->openFile($key);
            $file->open("w");
            $file->write(strval($ttl)."\r\n");
            $file->write($value);
            $file->flush();
            $file->close();
        }
    
        public function forget(string $key): bool {
            return $this->directory->delete($this->derive($key));
        }

        public function expired(string|File $key): bool {
            $file  = null;
            $close = false;

            if(is_string($key)) {
                $derived = $this->derive($key);

                if($this->directory->hasFile($derived)) {
                    $file = $this->directory->openFile($derived);
                    $close = true;
                }
            }
            else {
                $file = $key;
            }

            if($file !== null) {
                if(!$file->isOpen())
                    $file->open("r");
                
                $ttl = \Real::tryparse($file->readUntil("\r\n"));
                $file->relseek(-1);

                if($ttl === null)
                    throw new \Error("Unable to parse the ttl for cache key '". $file->path->getFilename() ."'.");

                if($close)
                    $file->close();

                return $ttl < microtime(true) && $ttl !== -1.0;
            }

            return true;
        }

        public function modify(string $key, Closure $callback, DateTimeInterface|DateInterval|int|null $ttl = null): bool {
            $state = false;

            if($this->has($key)) {
                $file = $this->directory->openFile($this->derive($key));
                $file->open("c+");

                if(!$this->expired($file)) {
                    $file->anchor();

                    $value = $file->readAll();

                    $file->revert();

                    $file->truncate($file->tell()+1);

                    $file->write(
                        $this->serialize(
                            $callback(
                                $this->deserialize($value)
                            )
                        )
                    );

                    $state = true;
                }
                else {
                    $state = false;
                }

                $file->close();
            }
            else {
                $this->put($key, $callback(null), $ttl);
                $state = true;
            }

            return $state;
        }
    
        public function pull(string $key, mixed $default = null): mixed {
            $derived = $this->derive($key);
            $got = false;

            if($this->directory->hasFile($derived)) {

                $file = $this->directory->openFile($derived);
                $file->open("r");

                if(!$this->expired($file)) {
                    $value = $this->deserialize($all = $file->readAll());

                    $got = true;

                    if(\Cls::hasInterface(static::class, IRepositoryHit::class)) {
                        $this->hit($key, $derived, $value);
                    }

                    $file->close();
                }
                else {
                    $file->close();
                    
                    if($this->autoforget)
                        $this->forget($key);
                }
            }
            else if(\Cls::hasInterface(static::class, IRepositoryMiss::class)) {
                $got = true;
                $value = $this->miss($key, $derived);
            }
            
            return $got ? $value : (
                $default instanceof Closure
                    ? $default()
                    : $default
            );
        }
    
        public function derive(string $key): string {
            return hash("sha256", $key);
        }
    }
}

?>