<?php

namespace Slate\File {
    use Slate\Data\Contract\IArrayAccess;
    use Slate\IO\File;
    use Slate\File\JsonFile\JsonFileValue;
    use Slate\File\Rotation\FileRotator;
    use SplFileInfo;

    class JsonFile extends File implements IArrayAccess {
        protected JsonFileValue $value;

        public function __construct(string|SplFileInfo $path, array $items = [], string|FileRotator $rotator = null) {
            parent::__construct($path, null, $rotator);

            $this->value = new JsonFileValue($this);
            $this->value->fromArray($items);
        }

        public function save(): void {
            $this->writeEx(json_encode($this->value->toArray(), JSON_PRETTY_PRINT));
            $this->flush();
        }

        public function load(): void {
            $this->value->fromArray(\Json::tryparse($this->readAll()));
            $this->rewind();
        }

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