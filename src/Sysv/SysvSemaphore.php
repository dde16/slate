<?php declare(strict_types = 1);
namespace Slate\Sysv {
    class SysvSemaphore extends SysvResource {
        protected int           $maxAcquire;
        protected bool          $autoRelease;

        public function __construct(int $key, int $maxAcquire = 1 , int $permissions = 0666 , bool $autoRelease = true) {
            $this->key         = $key;
            $this->maxAcquire  = $maxAcquire;
            $this->permissions = $permissions;
            $this->autoRelease = $autoRelease;

            $semaphore = \sem_get($key, $maxAcquire, $permissions, $autoRelease);

            if($semaphore === false)
                throw new \Error(\Str::format(
                    "Failed to get the semaphore by the key '{}'",
                    $key
                ));

            $this->resource = $semaphore;
        }

        public function acquire(bool $block = true): void {
            if(!\sem_acquire($this->resource, !$block)) {
                throw new \Error("Unable to acquire the Semaphore.");
            }
        }

        public function release(): bool {
            $stat = true;

            if($this->resource !== null) {
                $stat = \sem_release($this->resource);
                $this->resource = null;
            }

            return $stat;
        }

        public function destroy(): bool {
            $this->assertAcquired();

            $stat = \sem_remove($this->resource);
            $this->resource = null;

            return $stat;
        }
    }
}

?>