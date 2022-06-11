<?php declare(strict_types = 1);

namespace Slate\IO {
    enum StreamEofMethod: int {
        case FEOF = (1<<0);
        case POINTER = (1<<1);
        case UNREAD_BYTES = (1<<2);
    }
}

?>