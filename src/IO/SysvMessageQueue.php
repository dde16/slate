<?php

namespace Slate\IO {
    class SysvMessageQueue extends SysvResource {
        /** @see msg_queue_exists */
        public static function exists(int $key): bool {
            return \msg_queue_exists($key); 
        }

        public function __construct(int $key, int $permissions = 0600) {
            $this->key         = $key;
            $this->permissions = $permissions;
        }


        public function acquire(bool $create = true): void {
            if(!SysvMessageQueue::exists($this->key) && $create === false)
                throw new \Error("SysvMessageQueue by that key doesn't exist.");

            $this->resource = \msg_get_queue($this->key, $this->permissions);
            $this->assertAcquisition();
        }

        public function send(int $type, string|int|float|bool $message, bool $serialise = true, bool $block = true): void {
            $this->assertAcquired();

            $errorCode = null;

            if(!\msg_send($this->resource, $type, $message, $serialise, $block, $errorCode)) {
                throw new \Error(\Str::format("Error {} while recieving queue message.", $errorCode));
            }
        }

        public function recieve(int $type, int $maxSize, bool $unserialise = true, int $flags = 0): mixed {
            $this->assertAcquired();

            $recievedMessageType = $message = $errorCode = null;

            if(\msg_receive($this->resource, $type, $recievedMessageType, $maxSize, $message, $unserialise, $flags, $errorCode)) {
                return $message;
            }
            
            throw new \Error(\Str::format("Error {} while recieving queue messag ", $errorCode));
        }

        public function stat(): array|false {
            return \msg_stat_queue($this->resource);
        }

        public function release(): bool {
            return true;
        }

        public function destroy(): bool {
            $stat = true;

            if($this->resource !== null) {
                $stat = \msg_remove_queue($this->resource);
                $this->resource = null;
            }

            return $stat;
        }
    }
}

?>