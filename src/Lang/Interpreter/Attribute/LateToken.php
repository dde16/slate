<?php

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    
    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    class LateToken extends ExpressionToken {
        protected int     $length;
        protected bool    $defined;

        public function __construct(mixed $expression) {
            $this->defined = false;
        }

        public function define(string $expression): void {
            $this->expression    = $expression;
            $this->length = strlen($expression);
            $this->defined = true;
        }

        public function getLength(): int {
            return $this->length;
        }

        public function isDefined(): bool {
            return $this->defined;
        }
    }
}

?>