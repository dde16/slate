<?php

namespace Slate\Data {
    interface IStringForwardConvertable {
        function toString(): string;
        function __toString(): string;
    }
}

?>