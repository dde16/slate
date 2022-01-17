<?php

namespace Slate\IO {

    use Generator;

    /**
     * This class is very similar to the hashmap except that it has the ability to store keys.
     */
    class SysvSharedMemoryDictionary extends SysvSharedMemoryHashmap {
        protected SysvSharedMemoryLinkedList $keys;

        protected ?int $linkedListKey = null;
        protected ?int $linkedListSize = null;
        protected ?int $linkedListPermissions = null;

        public function __construct(
            int $dictionaryKey, int $dictionarySize, int $dictionaryPermissions,
            int $linkedListKey = null, int $linkedListSize = null, int $linkedListPermissions= null
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
            foreach($this->keys as $hashcode) 
                foreach(\Arr::keys($this->pull($hashcode)) as $key)
                    yield $key;
        }

        public function offsetAssign($offset, $value): void {
            $offset = strval($offset);
            $hashcode = \Str::hashcode($offset);

            if(!$this->has($hashcode))
                $this->keys[] = $hashcode;

            $map = $this->has($hashcode) ? $this->pull($hashcode) : [];

            $map[$offset] = $value;

            $this->put($hashcode, $map);
        }

        public function offsetUnset($offset): void {
            $offset = strval($offset);
            $hashcode = \Str::hashcode($offset);

            if($this->has($hashcode)) {
                $map = $this->pull($hashcode);

                unset($map[$offset]);

                if(\Arr::isEmpty($map)) {
                    $this->remove($hashcode);

                    foreach($this->keys as $index => $key) 
                        if($key === $hashcode) 
                            $this->keys->offsetUnset($index);
                }
                else {
                    $this->put($hashcode, $map);
                }
            }
        }
    }
}

?>