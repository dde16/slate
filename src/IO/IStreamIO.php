<?php

namespace Slate\IO {
    interface IStreamIO {
        public function isOpen(): bool;
        public function close(): bool;
        public function assertOpen(string $message = null): void;
    }
}

?>