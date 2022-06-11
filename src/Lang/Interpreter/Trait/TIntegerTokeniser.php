<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Trait {
    trait TIntegerTokeniser {
        use TRaisable;

        public function intTokeniser(int $digitToken, bool $raiseEof = false): ?array {
            $this->code->anchor();

            if(!($eof = $this->code->isEof())) {
                $start = $this->code->tell();
                $match = false;
    
                while($this->matchToken($digitToken))
                    $match = true;

                if($match)
                    return [$start, $this->code->tell() - $start];
            }

            if($eof && $raiseEof)
                $this->raiseEof();

            $this->code->revert();

            return null;
        }
    }
}

?>