<?php

namespace Slate\IO {
    interface IStreamReadable {
        public function isEof(): bool;
        public function readAll(): ?string;
        public function pipe(IStreamWriteable $stream, int $bufferSize = 8096): void;
        public function read(int $length = null, bool $eofNull = false): ?string;
        public function readChar(): ?string;
        public function readByte(): ?int;
        public function readUntil(string $until, bool $eof = true): ?string;
    }
}

?>