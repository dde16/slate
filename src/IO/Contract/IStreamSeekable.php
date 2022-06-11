<?php declare(strict_types = 1);

namespace Slate\IO\Contract {

    use Slate\Data\Iterator\Contract\ISeekableIterator;

    interface IStreamSeekable extends ISeekableIterator {
        function tell(): int|false;
        function rewind(): void;
        function seek(int $pointer): bool;
        function relseek(int $position): bool;
    }
}

?>