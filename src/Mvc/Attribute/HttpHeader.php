<?php

namespace Slate\Mvc\Attribute {

    use Slate\Metalang\MetalangAttribute;

    //TODO: implement
    class HttpHeader extends MetalangAttribute {
        protected string $name;
        protected ?string $value;

        public function __construct(string $name, string|int|float|bool $value = null) {
            $this->name = $name;

            if($value) {
                if(is_bool($value))
                    $value = $value ? '1' : '0';

                $this->value = $value !== null ? strval($value) : null;
            }
        }

        public function getName(): string {
            return $this->name;
        }

        public function getValue(): string|null {
            return $this->value;
        }
    }
}

?>