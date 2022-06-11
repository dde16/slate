<?php

namespace Slate\File\JsonFile {

    use Slate\Data\BasicArray;
    use Slate\File\JsonFile;

    class JsonFileValue extends BasicArray {
        protected JsonFile $file;
        protected bool     $save;

        public function __construct(JsonFile $file, array $items = []) {
            $this->file = $file;
            parent::__construct($items);
        }

        public function offsetAssign(string|int $offset, mixed $value): void {
            if(is_array($value)) {
                $value = new JsonFileValue($this->file, $value);
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
                $this->file->save();
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