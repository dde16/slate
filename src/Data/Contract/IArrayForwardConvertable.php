<?php declare(strict_types = 1);

namespace Slate\Data\Contract {
    interface IArrayForwardConvertable extends IForwardConvertable {
        function toArray(): array;
    }
}

?>