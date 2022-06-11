<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

    abstract class ExpressionToken extends Token {
        protected mixed  $expression;

        public function getExpression(): mixed {
            return $this->expression;
        }
    }
}

?>