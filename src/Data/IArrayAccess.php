<?php

namespace Slate\Data {

    use ArrayAccess;

    interface IArrayAccess extends ArrayAccess  {
        function offsetPush(mixed $value): void;
        function offsetAssign(string|int $offset, mixed $value): void;
    }
}

?>