<?php

namespace Slate\Lang {
    use Slate\Lang\Interpreter\InterpreterClass;

    use Slate\Lang\Interpreter\TParser;
    use Slate\Lang\Interpreter\TTokeniser;
    
    abstract class Parser extends InterpreterClass {
        use TParser;
        use TTokeniser;
    }
}

?>