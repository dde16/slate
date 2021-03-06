<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;
    use Slate\Metalang\MetalangAttribute;
    
    #[Attribute(Attribute::TARGET_METHOD)]
    class ComplexTokeniser extends MetalangAttribute {
        protected int $token;

        public function getKeys(): string|array {
            return strval($this->token);
        }

        public function __construct(int $token) {
            $this->token = $token;
        }
    }
}

?>