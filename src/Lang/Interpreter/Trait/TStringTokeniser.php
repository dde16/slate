<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Trait {
    trait TStringTokeniser {
        use TRaisable;

        public function stringTokeniser(int $doubleQuoteToken, bool $raiseEof = false): array|null {
            $this->code->anchor();

            if(!($eof = $this->code->isEof())) {
                $start = $this->code->tell();
            
                if($this->matchToken($doubleQuoteToken)) {
                    $gracefulExit = false;
            
                    while(!$gracefulExit && !$this->code->isEof()) {
                        $this->code->next();
                        
                        if(($this->code->current() !== "\\" || $this->code->next()) && ($quoteMatch = $this->matchToken($doubleQuoteToken))) {
                            $gracefulExit = true;
                        }
                    }
            
                    if($gracefulExit) {
                        return [$start, $this->code->tell() - $start];
                    }
                }
            }

            if($eof && $raiseEof)
                $this->raiseEof();

            $this->code->revert();
        
            return null;
        }
    }
}

?>