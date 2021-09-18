<?php

namespace Slate\Data {
    interface IArrayBackwardStaticConvertable extends IBackwardConvertable {
        static function fromArray(array $array): static;
    }
}

?>