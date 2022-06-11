<?php

namespace Slate\IO\Directory {

    use Iterator;
    use Slate\Data\TIterable;
    use Slate\IO\Directory;

    abstract class DirectoryOrderedIterator implements Iterator {
        protected Directory $directory;
        protected string $order;

        public const CONTAINER = "items";

        use TIterable {
            TIterable::rewind as protected _rewind;
        }

        public function __construct(Directory $directory, string $order) {
            $this->directory = $directory;
            $this->order = $order;
            $this->order();
        }

        public function rewind(): void {
            $this->_rewind();
            $this->order();
        }

        protected abstract function order(): void;
    }
}

?>