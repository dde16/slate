<?php declare(strict_types = 1);

namespace Slate\Sysv {
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

        public function send(string|int|float|bool $message, int $type = 1, bool $serialise = true, bool $block = true): void {
            $this->assertAcquired();

            $errorCode = null;

            if(!\msg_send($this->resource, $type, $message, $serialise, $block, $errorCode)) {
                throw new \Error(\Str::format("Error {} while sending queue message.", $errorCode));
            }
        }

        public function receive(int $maxSize, int $type = 0, bool $unserialise = true, int $flags = 0): mixed {
            $this->assertAcquired();

            $receivedMessageType =
            $message =
            $errorCode = null;

            if(!msg_receive($this->resource, $type, $receivedMessageType, $maxSize, $message, $unserialise, $flags, $errorCode)) {
                throw new \Error("Error $errorCode while receiving queue message.");
            }
            
            return $message;
            
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