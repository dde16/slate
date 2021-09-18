<?php

namespace Slate\Data {
    interface IStringBackwardConvertable {
        function fromString(string $string): void;
    }
}

?>