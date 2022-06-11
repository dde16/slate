<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    class ComplexToken extends Token { 
        public function getImplementor(): object {
            return $this->implementor;
        }
    }
}

?>