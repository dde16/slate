<?php

namespace Slate\IO {
    trait TStreamIterable {
        /** @see Iterator::current() */
        public function current(): mixed {
            $char = $this->readChar();

            $this->prev();

            return $char;
        }

        /** @see Iterator::key() */
        public function key(): mixed {
            return $this->tell();
        }

        /** @see Iterator::prev() */
        public function prev(): void {
            $this->relseek(-1);
        }

        /** @see Iterator::next() */
        public function next(): void {
            $this->relseek(1);
        }

        public function rewind(): void {
            $this->seek(0);
        }

        /** @see Iterator::valid() */
        public function valid(): bool {
            return !$this->isEof();
        }
    }
}

?>