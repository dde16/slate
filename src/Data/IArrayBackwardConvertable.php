<?php

namespace Slate\Data {
    interface IArrayBackwardConvertable extends IBackwardConvertable {
        function fromArray(array $array): void;
    }
}

?>