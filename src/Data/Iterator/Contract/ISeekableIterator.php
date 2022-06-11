<?php declare(strict_types = 1);

namespace Slate\Data\Iterator\Contract {
    interface ISeekableIterator {
        function seek(int $pointer);
    }
}