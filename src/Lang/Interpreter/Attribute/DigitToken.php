<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    class DigitToken extends CompoundToken   {
        public function __construct() {
            parent::__construct('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');
        }
    }
}

?>