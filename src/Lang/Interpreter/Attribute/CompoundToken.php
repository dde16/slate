<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    class CompoundToken extends ExpressionToken {

        public function __construct(string|int ...$expressions) {
            $expressions = \Str::fromByteArray($expressions);
            
            /** Sort in length order */
            uasort($expressions, fn($a, $b) => strlen($b) - strlen($a));

            $this->expression = \Arr::values(\Arr::map($expressions, fn($choice): array => [$choice, strlen($choice)]));
        }
    }
}

?>