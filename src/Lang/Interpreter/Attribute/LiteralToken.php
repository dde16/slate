<?php

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    class LiteralToken extends ExpressionToken {
        protected int    $length;

        public function __construct(string $expression) {
            $this->expression    = $expression;
            $this->length = strlen($expression);
        }

        public function getLength(): int {
            return $this->length;
        }
    }
}

?>