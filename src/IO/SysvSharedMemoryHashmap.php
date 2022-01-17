<?php

namespace Slate\IO {
    use ArrayAccess;
    use Countable;
    use Slate\Data\TOffsetExtended;

    class SysvSharedMemoryHashmap extends SysvSharedMemory implements ArrayAccess {
        public function offsetAssign($offset, $value): void {
            $offset = strval($offset);
            $hashcode = \Str::hashcode($offset);
            $map = $this->has($hashcode) ? $this->pull($hashcode) : [];

            $map[$offset] = $value;

            $this->put($hashcode, $map);
        }

        public function offsetPush($value): void {
            $this->offsetAssign(null, $value);
        }

        public function offsetExists($offset): bool {
            $offset = strval($offset);
            $hashcode = \Str::hashcode($offset);
            
            return $this->has($hashcode) ? \Arr::hasKey($this->pull($hashcode), $offset) : false;
        }

        public function offsetUnset($offset): void {
            $offset = strval($offset);
            $hashcode = \Str::hashcode($offset);

            if($this->has($hashcode)) {
                $map = $this->pull($hashcode);

                unset($map[$offset]);

                if(\Arr::isEmpty($map)) {
                    $this->remove($hashcode);
                }
                else {
                    $this->put($hashcode, $map);
                }
            }
        }

        public function offsetGet($offset): mixed {
            $offset = strval($offset);
            $hashcode = \Str::hashcode($offset);

            return $this->has($hashcode) ? $this->pull(\Str::hashcode($offset))[$offset] : null;
        }
    }
}

?>