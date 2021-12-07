<?php

namespace Slate\IO {
    interface IStreamAuditable {
        public function getResource(): mixed;
        public function getStatistics(): array;
        public function getSize(): int;
    }
}

?>