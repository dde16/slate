<?php

namespace Slate\Lang\Interpreter {
    use Slate\Metalang\MetalangClass;

    use Slate\Lang\Interpreter\InterpreterDesign;
    
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter\ICodeable;

abstract class InterpreterClass extends MetalangClass {
        public const DESIGN = InterpreterDesign::class;

        public function interpret(object &$code, array &$arguments = []) {
            $this->code         = $code;
            $this->tokenMatches = $this->tokenise(true);
            
            return $this->evaluate($this->parse(), $arguments);
        }
    }
}

?>