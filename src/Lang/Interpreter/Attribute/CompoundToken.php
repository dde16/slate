<?php

namespace Slate\Lang\Interpreter\Attribute {
    use Attribute;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

    #[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
    class CompoundToken extends ExpressionToken {

        public function __construct(string ...$expressions) {
            
            foreach($expressions as $token) {
                if(!is_string($token))
                    throw new \Error("Array provided to CompoundToken must contain strings exclusively.");
            }
            
            uasort(
                $expressions,
                function($a, $b) {
                    return strlen($b) - strlen($a);
                }
            );

            $this->expression = \Arr::values(
                \Arr::map(
                    $expressions,
                    function($choice) {
                        return [$choice, strlen($choice)];
                    }
                )
            );
        }
    }
}

?>