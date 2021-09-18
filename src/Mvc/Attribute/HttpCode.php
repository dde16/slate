<?php

namespace Slate\Mvc\Attribute {

    use Slate\Metalang\MetalangAttribute;

    //TODO: implement
    class HttpCode extends MetalangAttribute {
        protected string $code;

        public function __construct(int $code) {
            $this->code = $code;
        }

        public function getCode(): int {
            return $this->code;
        }
    }
}

?>