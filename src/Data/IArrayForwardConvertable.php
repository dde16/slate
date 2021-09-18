<?php

namespace Slate\Data {
    interface IArrayForwardConvertable extends IForwardConvertable {
        function toArray(): array;
    }
}

?>