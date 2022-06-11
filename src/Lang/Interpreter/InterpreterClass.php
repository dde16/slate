<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter {
    use Slate\Metalang\MetalangClass;
    use Slate\Lang\Interpreter\InterpreterDesign;

    abstract class InterpreterClass extends MetalangClass {
        public const DESIGN = InterpreterDesign::class;

        public function interpret(object &$code, array &$arguments = []) {
            $this->code = $code;
            
            return $this->evaluateTree(
                $this->parse(),
                $arguments
            );
        }
    }
}

?>