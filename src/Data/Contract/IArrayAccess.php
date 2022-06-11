<?php declare(strict_types = 1);

namespace Slate\Data\Contract {

    use ArrayAccess;

    interface IArrayAccess extends ArrayAccess  {
        function offsetPush(mixed $value): void;
        function offsetAssign(string|int $offset, mixed $value): void;
    }
}

?>