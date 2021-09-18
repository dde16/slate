<?php

namespace Slate\IO {
    use ArrayAccess;
    use Closure;
    use Slate\Data\TOffsetExtended;
    use Slate\Data\Iterator\IExtendedIterator;

    class SysvSharedMemory extends SysvResource implements ArrayAccess {
        use TOffsetExtended;

        protected int        $size;

        public function __construct(int $key, int $size, int $permissions = 0600) {
            $this->key  = $key;
            $this->size = $size;
            $this->permissions = $permissions;

            $this->acquire();
        }

        public function offsetAssign($index, $value): void {
            $this->put($index, $value);
        }

        public function offsetPush(mixed $value): void {
            throw new \Error("Cannot push values to Shared Memory.");
        }

        public function offsetExists($index): bool {
            return $this->has($index);
        }

        public function offsetUnset($index): void {
            $this->remove($index);
        }

        public function offsetGet($index): mixed {
            return $this->pull($index);
        }

        public function acquire(): void {
            $this->resource = \shm_attach($this->key, $this->size, $this->permissions);
            $this->assertAcquisition();
        }

        public function reacquire(): void {
            if($this->resource !== null)
                $this->release();

            $this->acquire();
        }

        public function preIncrement(int $variableKey): int|false {
            $this->assertAcquired();

            if($this->has($variableKey)) {
                $value = $this->pull($variableKey);
                $this->put($variableKey, ($value+1));

                return $value;
            }

            return false;
        }

        public function postIncrement(int $variableKey): int|false {
            $this->assertAcquired();

            if($this->has($variableKey)) {
                $this->put($variableKey, ($value = $this->pull($variableKey)+1));

                return $value;
            }

            return false;
        }

        public function getSize(): int {
            return $this->size;
        }

        public function preDecrement(int $variableKey): int|false {
            $this->assertAcquired();

            if($this->has($variableKey)) {
                $value = $this->pull($variableKey);
                $this->put($variableKey, $value - 1);

                return $value;
            }

            return false;
        }

        public function postDecrement(int $variableKey): int|false {
            $this->assertAcquired();

            if($this->has($variableKey)) {
                $this->put($variableKey, $value = $this->pull($variableKey)-1);

                return $value;
            }

            return false;
        }

        public function modify(int $variableKey, Closure $callback): void {
            $this->assertAcquired();

            $variable = null;

            if($this->has($variableKey)) {
                $variable = $this->pull($variableKey);
            }

            $variable = $callback($variable);

            $this->put($variableKey, $variable);
        }

        public function update(int $variableKey, string $path, $value): mixed {
            $this->assertAcquired();

            if($this->has($variableKey)) {
                $variable = $this->pull($variableKey);

                if(!(is_object($variable) && is_array($variable)))
                    throw new \Error();

                \Compound::set($variable, $path, $value);

                return $this->put($variableKey, $variable);
            }

            return false;
        }

        public function put(int $variableKey, $value): bool {
            $this->assertAcquired();

            return \shm_put_var($this->resource, $variableKey, $value);
        }

        public function has(int $variableKey): bool {
            $this->assertAcquired();

            return \shm_has_var($this->resource, $variableKey);
        }

        public function pull(int $variableKey): mixed {
            $this->assertAcquired();

            return \shm_get_var($this->resource, $variableKey);
        }

        public function remove(int $variableKey): bool {
            $this->assertAcquired();

            return \shm_remove_var($this->resource, $variableKey);
        }

        public function destroy(): bool {
            $this->assertAcquired();

            $stat = \shm_remove($this->resource);
            $this->resource = null;

            return $stat;
        }

        public function release(): bool {
            $stat = true;

            if($this->resource !== null) {
                $stat = \shm_detach($this->resource);
                $this->resource = null;
            }

            return $stat;
        }
    }
}

?>