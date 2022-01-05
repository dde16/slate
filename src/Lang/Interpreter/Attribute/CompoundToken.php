<?php

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    class CompoundToken extends ExpressionToken {

        public function __construct(string ...$expressions) {
            
            foreach($expressions as $token)
                if(!is_string($token))
                    throw new \Error("Array provided to CompoundToken must contain strings exclusively.");
            
            /** Sort in length order */
            uasort($expressions, fn($a, $b) => strlen($b) - strlen($a));

            $this->expression = \Arr::values(\Arr::map($expressions, fn($choice): array => [$choice, strlen($choice)]));
        }
    }
}

?>