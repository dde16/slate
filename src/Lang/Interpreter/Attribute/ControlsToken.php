<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    class ControlsToken extends RangeToken   {
        public function __construct() {
            parent::__construct(0, 32);
        }
    }
}

?>