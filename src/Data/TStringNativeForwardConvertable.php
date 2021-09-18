<?php

namespace Slate\Data {
    trait TStringNativeForwardConvertable {
        public function __toString(): string {
            return $this->toString();
        }
    }
}

?>