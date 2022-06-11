<?php declare(strict_types = 1);

namespace Slate\IO\Contract {
    interface IStreamAuditable {
        public function getResource(): mixed;
        public function getStatistics(): array;
        public function getSize(): int;
    }
}

?>