<?php declare(strict_types = 1);

namespace Slate\Data\ArrayProxy {
    use Slate\Data\ArrayProxy;
    use Slate\Data\BasicArray;

    class ArrayProxyValue extends BasicArray {
        protected ArrayProxy $proxy;
        protected bool     $save;

        public function __construct(ArrayProxy $proxy, array $items = []) {
            $this->proxy = $proxy;
            parent::__construct($items);
        }

        public function offsetAssign(string|int $offset, mixed $value): void {
            if(is_array($value)) {
                $value = new static($this->proxy, $value);
            }

            parent::offsetAssign($offset, $value);

            $this->save();
        }

        public function offsetPush(mixed $value): void {
            parent::offsetPush($value);

            $this->save();
        }

        public function offsetUnset(mixed $offset): void {
            parent::offsetUnset($offset);

            $this->save();
        }

        public function save(): void {
            if($this->save)
                $this->proxy->save();
        }

        public function fromArray(array $array): void {
            $this->save = false;
            foreach($array as $key => $value) {
                $this[$key] = $value;
            }
            $this->save = true;
        }
    }
}

?>