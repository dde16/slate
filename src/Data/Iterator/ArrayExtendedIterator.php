<?php

namespace Slate\Data\Iterator {
    use ArrayIterator;
    use Slate\Data\Iterator\IExtendedIterator;

    class ArrayExtendedIterator extends ArrayIterator implements IExtendedIterator, IAnchoredIterator {
        use TAnchoredIterator;

        public function prev(): void {
            $this->seek(intval($this->key())-1);
        }
    }
}

?>