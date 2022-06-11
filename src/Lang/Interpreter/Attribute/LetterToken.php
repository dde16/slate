<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Attribute {

    use BadMethodCallException;

    class LetterToken extends RangeToken   {
        public function __construct(string $space = "both") {
            $space = \Str::lower($space);

            $from = "a";
            $to = "z";

            switch($space) {
                case "lower":
                case "lowercase":
                    break;
                case "upper":
                case "uppercase":
                    $from = strtoupper($from);
                    $to = strtoupper($from);
                    break;
                case "both":
                    $from = strtoupper($from);
                    break;
                default:
                    throw new BadMethodCallException("Invalid letter space '$space', must be upper(case)/lower(case).");
                    break;
            }

            parent::__construct($from, $to);
        }
    }
}

?>