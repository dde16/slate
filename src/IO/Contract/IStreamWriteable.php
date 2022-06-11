<?php declare(strict_types = 1);

namespace Slate\IO\Contract {
    interface IStreamWriteable extends IStreamSeekable {
        public function writebyte(int $data): void;
        public function write(string $data, int $size = null): void;
        public function truncate(int $size = null): void;
        public function flush(): bool;
    }
}

?>