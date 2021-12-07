<?php

namespace Slate\IO {
    interface IStreamSeekable {
        public function tell(): int|false;
        public function rewind(): void;
        public function seek(int $position): bool;
        public function relseek(int $position): bool;
    }
}

?>