<?php

namespace Slate\Data\Iterator {
    use ArrayIterator;
    use Slate\Data\Iterator\IExtendedIterator;

    class ArrayExtendedIterator extends ArrayIterator implements IExtendedIterator {
        public function prev(): void {
            prev($this);
        }
    }
}

?>