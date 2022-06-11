<?php declare(strict_types = 1);

namespace Slate\Data\Contract {
    interface IArrayBackwardConvertable extends IBackwardConvertable {
        static function fromArray(array $array): void;
    }
}

?>