<?php declare(strict_types = 1);

namespace Slate\Data {

    use Slate\Data\Contract\IArrayAccess;
    use Slate\Data\ArrayProxy\ArrayProxyValue;

    abstract class ArrayProxy implements IArrayAccess {
        protected ArrayProxyValue $value;

        public function __construct() {
            $this->value = new ArrayProxyValue($this);
        }

        public abstract function save(): void;

        public abstract function load(): void;

        public function offsetExists(mixed $offset): bool {
            return $this->value->offsetExists($offset);
        }

        public function offsetSet(mixed $offset, mixed $value): void {
            $this->value->offsetSet($offset, $value);
        }

        public function offsetGet(mixed $offset): mixed {
            return $this->value->offsetGet($offset);
        }

        public function offsetAssign(string|int $offset, mixed $value): void {
            $this->value->offsetAssign($offset, $value);
        }

        public function offsetPush(mixed $value): void {
            $this->value->offsetPush($value);
        }

        public function offsetUnset(mixed $offset): void {
            $this->value->offsetUnset($offset);
        }

        public function toArray(): array {
            return $this->value->toArray();
        }
    }
}

?>