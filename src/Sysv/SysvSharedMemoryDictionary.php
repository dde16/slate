<?php declare(strict_types = 1);
namespace Slate\Sysv {

    use Generator;
    use Slate\Exception\StackUnderflowException;

    /**
     * This class is very similar to the hashmap except that it has the ability to store keys.
     */
    class SysvSharedMemoryDictionary extends SysvSharedMemoryHashmap {
        protected SysvSharedMemoryLinkedList $keys;

        protected ?int $linkedListKey = null;
        protected ?int $linkedListSize = null;
        protected ?int $linkedListPermissions = null;

        public function __construct(
            int $dictionaryKey, int $dictionarySize, int $dictionaryPermissions = 0600,
            int $linkedListKey = null, int $linkedListSize = null, int $linkedListPermissions = null
        ) {
            $this->linkedListKey = $linkedListKey ?? $dictionaryKey+1;
            $this->linkedListSize = $linkedListSize ?? $dictionarySize;
            $this->linkedListPermissions = $linkedListPermissions ?? $dictionaryPermissions;

            parent::__construct($dictionaryKey, $dictionarySize, $dictionaryPermissions);
        }

        
        public function acquire(): void {
            $this->keys = new SysvSharedMemoryLinkedList($this->linkedListKey, $this->linkedListSize, $this->linkedListPermissions);

            parent::acquire();
        }

        public function release(): bool {
            return $this->keys->release() && parent::release();
        }

        public function destroy(): bool {
            return $this->keys->destroy() && parent::destroy();
        }

        public function keys(): Generator {
            foreach($this->keys as $hashcode) {
                foreach(\Arr::keys($this->pull($hashcode)) as $key) {
                    yield $key;
                }
            }
        }

        public function dequeue(): mixed {
            $keys = $this->keys();

            if($this->keys->isEmpty())
                throw new StackUnderflowException("This Hashmap is empty.");

            $key = $keys->current();
            $value = $this[$key];
            $this->offsetUnset($key);

            return $value;
        }

        public function offsetGet($offset): mixed {
            $value = parent::offsetGet($offset);

            if($value === null)
                return null;

            return $value[0];
        }

        public function offsetAssign($offset, $value): void {
            $offset = strval($offset);
            $hashcode = \Str::hashcode($offset);
            $map = $this->has($hashcode) ? $this->pull($hashcode) : [];

            if($this->offsetExists($offset)) {
                $this->keys->offsetUnset($map[$offset][1]);
            }

            $map[$offset] = [$value, $this->keys->push($hashcode)];

            $this->put($hashcode, $map);
        }

        public function offsetUnset($offset): void {
            $offset = strval($offset);
            $hashcode = \Str::hashcode($offset);

            if($this->has($hashcode)) {
                $map = $this->pull($hashcode);
                $keyIndex = $map[$offset][1];

                if(\Arr::isEmpty($map)) {
                    $this->remove($hashcode);
                }
                else {
                    $this->put($hashcode, $map);
                }

                $this->keys->offsetUnset($keyIndex);
            }
        }
    }
}

?>