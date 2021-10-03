<?php

namespace Slate\Utility {
    trait TLock {
        /**
         * @var int $lock
         * Flag whether the current object is locked and what is locked.
         */
        protected int $lock = 0;

        /**
         * A function that will lock the current object.
         * If the object is already locked, lock parameters will merge.
         * 
         * @param int $lock What constructs will be locked, which uses the Attribute::TARGET_* constants.
         * 
         * @return void
         */
        public function lock(int $lock): void {
            $this->lock |= $lock;
        }

        /**
         * Check whether the object is locked.
         * 
         * @param int $construct Filter by construct.
         * 
         * @return bool
         */
        public function locked(int $construct = 0): bool {
            return ($construct && $this->lock) ? ($this->lock & $construct) : $this->lock;
        }

        /**
         * Unlock the object 
         */
        protected function unlock(int $construct = 0): void {
            if($construct > 0 ? ($this->lock & $construct) : false) {
                $this->lock &= ~$construct;
            }
            else {
                $this->lock = 0;
            }
        }
    }
}

?>