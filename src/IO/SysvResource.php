<?php


namespace Slate\IO {
    abstract class SysvResource {
        protected int           $key;
        protected int           $permissions;

        protected               $resource;

        public function isAcquired(): bool {
            return $this->resource !== null && $this->resource !== false;
        }

        public function assertAcquired(): void {
            if(!$this->isAcquired())
                throw new \Error(\Str::format(
                    "{}(key={}) has not been acquired.",
                    \Str::afterFirst(static::class, "\\"),
                    $this->key
                ));
        }

        public function assertAcquisition(): void {
            if(!$this->isAcquired())
                throw new \Error(\Str::format(
                    "Error while acquiring {}(key={}): {}.",
                    $this->key, \error_get_last()["message"]
                ));
        }

        public abstract function acquire(): void;
        public abstract function destroy(): bool;
        public abstract function release(): bool;
    }
}

?>