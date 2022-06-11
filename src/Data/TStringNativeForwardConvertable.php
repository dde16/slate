<?php declare(strict_types = 1);

namespace Slate\Data {
    trait TStringNativeForwardConvertable {
        public function __toString(): string {
            return $this->toString();
        }
    }
}

?>