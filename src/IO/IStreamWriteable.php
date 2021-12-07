<?php

namespace Slate\IO {
    interface IStreamWriteable extends IStreamSeekable {
        public function writebyte(int $data): void;
        public function write(string $data, int $size = null): void;
        public function truncate(int $size = null): void;
        public function flush(): bool;
    }
}

?>