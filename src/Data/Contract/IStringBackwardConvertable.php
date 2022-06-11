<?php declare(strict_types = 1);

namespace Slate\Data\Contract {
    interface IStringBackwardConvertable {
        static function fromString(string $string): void;
    }
}

?>