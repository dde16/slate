<?php declare(strict_types = 1);

namespace Slate\Data\Contract {
    interface IArrayBackwardStaticConvertable extends IBackwardConvertable {
        static function fromArray(array $array): static;
    }
}

?>