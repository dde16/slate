<?php declare(strict_types = 1);

namespace Slate\Data\Iterator {
    use ArrayIterator;
    use Slate\Data\Iterator\Contract\ISeekableIterator;

    class ArrayExtendedIterator extends ArrayIterator implements ISeekableIterator, IExtendedIterator, IAnchoredIterator {
        use TAnchoredIterator;

        public function rest(): array {
            $array = [];

            while ($this->valid()) {
                $array[] = $this->current();
                $this->next();
            }

            return $array;
        }

        public function prev(): void {
            $this->seek(intval($this->key())-1);
        }

        public function chunk(int $size): array {
            $buffer = [];

            for ($index = 0; $index < $size && $this->valid(); $index++) {
                $buffer[] = $this->current();
                $this->next();
            }

            return $buffer;
        }
    }
}

?>