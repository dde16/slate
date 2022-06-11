<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;

    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    class LiteralToken extends ExpressionToken {
        protected int    $length;
        protected bool   $exact;

        public function __construct(string $expression, bool $exact = true) {
            $this->expression    = $expression;
            $this->exact = $exact;
            $this->length = strlen($expression);
        }

        public function getLength(): int {
            return $this->length;
        }

        public function isExact(): bool {
            return $this->exact;
        }
    }
}

?>