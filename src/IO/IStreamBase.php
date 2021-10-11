<?php

namespace Slate\IO {
    interface IStreamBase {
        public function getResource(): mixed;
        public function getStatistics(): array;
        public function getSize(): int;
        public function isOpen(): bool;
        public function tell(): int|false;
        public function rewind(): void;
        public function seek(int $position): bool;
        public function relseek(int $position): bool;
        public function close(): bool;
        public function assertOpen(string $message = null): void;
    }
}

?>