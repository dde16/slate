<?php declare(strict_types = 1);

namespace Slate\Lang {
    use Slate\Lang\Interpreter\TEvaluator;
    
    abstract class Interpreter extends Parser {
        use TEvaluator;
    }
}

?>