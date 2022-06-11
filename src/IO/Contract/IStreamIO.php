<?php declare(strict_types = 1);

namespace Slate\IO\Contract {
    interface IStreamIO {
        public function isOpen(): bool;
        public function close(): bool;
        public function assertOpen(string $message = null): void;
    }
}

?>