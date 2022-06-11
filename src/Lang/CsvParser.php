<?php declare(strict_types = 1);

namespace Slate\Lang {

    use Error;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter;
    use Slate\Lang\Interpreter\Attribute\ComplexToken;
    use Slate\Lang\Interpreter\Attribute\ComplexTokeniser;
    use Slate\Lang\Interpreter\Attribute\Evaluator;
    use Slate\Lang\Interpreter\Attribute\LiteralToken;

    class CsvParser extends Interpreter {        
        #[LiteralToken('"')]
        const TOKEN_QUOTE            = (1<<0);

        #[ComplexToken]
        const TOKEN_NEWLINE          = (1<<2);

        #[LiteralToken(",")]
        const TOKEN_COMMA            = (1<<3);
        
        #[ComplexToken]
        const TOKEN_FIELD            = (1<<4);

        #[ComplexTokeniser(CsvParser::TOKEN_NEWLINE)]
        public function expectNewLine(): ?array {
            $this->code->anchor();

            $start = $this->code->tell();

            if($this->code->current() === "\r")
                $this->code->next();
                
            if($this->code->current() === "\n") {
                $this->code->next();

                return [
                    $start,
                    ($this->code->tell() - $start)
                ];
            }

            $this->code->revert();

            return null;
        }

        #[ComplexTokeniser(CsvParser::TOKEN_FIELD)]
        public function expectOptionallyQuotedStringToken(): ?array {
            $this->code->anchor();
            $gracefulExit = false;
            $exitToken = null;
            $start = $this->code->tell();

            if($this->matchToken(self::TOKEN_QUOTE)) {
                while(!$gracefulExit && !$this->code->isEof()) {
                    if($this->code->current() === "\\") {
                        $this->code->next();
                        $this->code->next();
                    }
                    else if(($exitToken = ($this->matchToken(self::TOKEN_QUOTE))) !== null) {
                        $gracefulExit = true;
                    }
                    else {
                        $this->code->next();
                    }
                }
                    
                if($gracefulExit) {
                    $length = $this->code->tell() - $start;

                    return [$start, $length];
                }
            }
            else {
                
                while(!$gracefulExit) {
                    if($this->code->isEof()) {
                        $gracefulExit = true;
                    }
                    else if(($exitToken = ($this->matchToken(self::TOKEN_COMMA))) !== null) {
                        $gracefulExit = true;
                    }
                    else if(($exitToken = ($this->matchToken(self::TOKEN_NEWLINE))) !== null) {
                        $gracefulExit = true;
                    }
                    else {
                        $this->code->next();
                    }
                }

                if($gracefulExit) {
                    $this->code->relseek($exitToken[1]*-1);
                    $length = $this->code->tell() - $start;
    
                    return [$start, $length];
                }
            }

            $this->code->revert();

            return null;
        }

        public function parse(): \Generator {
            return $this->expectList(
                function() {
                    return [
                        "ROW",
                        $this->expectList(
                            function() {
                                return [
                                    "FIELD",
                                    [ $this->matchToken(self::TOKEN_FIELD) ] 
                                ];
                            },
                            self::TOKEN_COMMA,
                            raise: false
                        )
                    ];
                },
                self::TOKEN_NEWLINE,
                raise: true
            );
        }

        public function evaluate(array &$construct, array &$arguments): void {
            switch($construct[0]) {
                case "FIELD":
                    $this->evaluateField($construct);
                    break;
                case "ROW":
                    $this->evaluateRow($construct);
                    break;
            }
        }

        public function evaluateField(&$construct) {
            if($construct[1][0]) {
                $value = \Str::removeAffix($construct[1][0]->getValue(),'"');
                $construct = !\Str::isEmpty($value) ? $value : null;
            }
            else
                $construct = null;
        }

        public function evaluateRow(&$construct) {
            $construct = $construct[1];
        }
    }
}

?>