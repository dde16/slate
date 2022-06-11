<?php declare(strict_types = 1);

namespace Slate\Data\Contract {
    interface IStringForwardConvertable {
        function toString(): string;
        function __toString(): string;
    }
}

?>