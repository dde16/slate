<?php

namespace Slate\IO {

    use Closure;
    use DateInterval;
    use DateTimeInterface;
    use Slate\Data\Repository;

    class SysvSharedMemoryRepository extends Repository {
        public SysvSharedMemoryHashmap $hashmap;

        public function __construct(int $key, int $size, int $permissions = 0600, bool $autoforget = false) {
            parent::__construct($autoforget);

            $this->hashmap = new SysvSharedMemoryHashmap($key, $size, $permissions);
        }

        public function derive(string $key): string {
            return $key;
        }

        public function expired(string $key): bool {
            return $this->hashmap->offsetExists($key) ? ($this->hashmap[$key]["ttl"] > microtime(true)) : false;
        }
        
        public function until(string $key, mixed $value, DateTimeInterface|DateInterval|float|int $ttl = null): void {
            $ttl = $ttl !== null ? \Real::fromDateTime($ttl) : null;
    
            $this->hashmap[$key] = [
                "value" => $value,
                "ttl"   => $ttl
            ];
        }

        public function modify(string $key, Closure $callback, DateTimeInterface|DateInterval|int|null $ttl = null): bool {
            $modified = false;

            if($this->hashmap->offsetExists($key)) {
                if(($stored = $this->hashmap[$key]) !== null) {
                    if($stored["ttl"] > microtime(true) ) {
                        $stored["value"] = $callback($stored["value"]);

                        $this->hashmap[$key] = $stored;

                        $modified = true;
                    }
                }
            }

            if($modified === false) {
                $this->put($key, $callback(null), $ttl);
            }

            return true;
        }

        public function pull(string $key, mixed $default = null): mixed {
            $value  = $default;

            if($this->hashmap->offsetExists($key)) {
                $stored = $this->hashmap[$key];

                if($stored !== null) {
                    if($stored["ttl"] !== null) {
                        if($stored["ttl"] > microtime(true)) {
                            $value = $stored["value"];
                        }
                        else if($this->autoforget) {
                            $this->forget($key);
                        }
                    }
                    else {
                        $value = $stored["value"];
                    }
                }
            }

            return $value;
        }

        public function has(string $key): bool {
            return $this->hashmap->offsetExists($key);
        }

        public function forget(string $key): bool {
            $this->hashmap->offsetUnset($key);

            return true;
        }

        public function flush(): bool {
            $this->hashmap->destroy();
            $this->hashmap->acquire();

            return true;
        }
    }
}

?>