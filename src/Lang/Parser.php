<?php declare(strict_types = 1);

namespace Slate\Lang {
    use Slate\Lang\Interpreter\InterpreterClass;
    use Slate\Lang\Interpreter\TParser;
    
    abstract class Parser extends InterpreterClass {
        use TParser;
    }
}

?>