<?php

namespace Slate\Lang {
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;
    use Slate\Lang\Interpreter;
    use Slate\Lang\Interpreter\Attribute\ComplexToken;
    use Slate\Lang\Interpreter\Attribute\ComplexTokeniser;
    use Slate\Lang\Interpreter\Attribute\Evaluator;
    use Slate\Lang\Interpreter\Attribute\LiteralToken;

    class CsvParser extends Interpreter {
        const PRIORITY = [
            self::TOKEN_COMMA,
            self::TOKEN_CARRIAGE_RETURN,
            self::TOKEN_NEWLINE,
            self::TOKEN_FIELD,
            self::TOKEN_QUOTE
        ];

        const IGNORE = [
            self::TOKEN_CARRIAGE_RETURN
        ];
        
        #[LiteralToken('"')]
        const TOKEN_QUOTE            = (1<<0);

        #[LiteralToken("\r")]
        const TOKEN_CARRIAGE_RETURN  = (1<<1);

        #[LiteralToken("\n")]
        const TOKEN_NEWLINE          = (1<<2);

        #[LiteralToken(",")]
        const TOKEN_COMMA            = (1<<3);
        
        #[ComplexToken]
        const TOKEN_FIELD            = (1<<4);

        #[ComplexTokeniser(CsvParser::TOKEN_FIELD)]
        public function expectOptionallyQuotedStringToken(): ?array {
            $isQuoted = false;
            $this->code->anchor();
        
            if($this->matchToken(static::design()->tokens[self::TOKEN_QUOTE])) {
                $isQuoted = true;
            }

            $start = $this->code->tell();
            $gracefulExit = false;

            while(!$gracefulExit && !$this->code->isEof()) {
                $this->code->next();
                
                if($this->code->isEof()) {
                    $gracefulExit = true;
                }
                else if(($columnMatch = $this->matchToken(static::design()->tokens[self::TOKEN_COMMA])) && !$isQuoted) {
                    $gracefulExit = true;
                    $this->code->relseek($columnMatch[1]*-1);
                }
                else if(($delimMatch = ($this->matchToken(static::design()->tokens[self::TOKEN_NEWLINE]) ?: $this->matchToken(static::design()->tokens[self::TOKEN_CARRIAGE_RETURN])))) {
                    $gracefulExit = true;
                    $this->code->relseek($delimMatch[1]*-1);
                }
                else if(($this->code->current() !== "\\" || $this->code->next()) && ($quoteMatch = $this->matchToken(static::design()->tokens[self::TOKEN_QUOTE])) && $isQuoted) {
                    $gracefulExit = true;
                }
            }

            if(($isQuoted && $gracefulExit) || $gracefulExit || $this->code->isEof()) {
                $length = $this->code->tell() - $start - ($isQuoted ? 1 : 0);
                return [$start, $length];
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
                                    [ $this->expectToken(self::TOKEN_FIELD) ?: NULL ] 
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

        #[Evaluator("FIELD")]
        public function evaluateField(&$construct) {
            if($construct[1][0])
                $construct = $construct[1][0]->getValue();
            else
                $construct = null;
        }

        #[Evaluator("ROW")]
        public function evaluateRow(&$construct) {
            $construct = $construct[1];
        }
    }
}

?>