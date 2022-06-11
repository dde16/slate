<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Trait {
    trait TFloatTokeniser {
        use TRaisable;

        public function floatTokeniser(int $digitToken, int $dotToken, bool $raiseEof = false): array|null {
            $this->code->anchor();

            if(!($eof = $this->code->isEof())) {
                $start = $this->code->tell();
                $rationalMatch = false;
                $dotMatch = false;
                $irrationalMatch = false;

                while($this->matchToken($digitToken)) {
                    $rationalMatch = true;
                }

                if($this->matchToken($dotToken)) {
                    $dotMatch = true;

                    while($this->matchToken($digitToken))
                        $irrationalMatch = true;
                }

                if($dotMatch || ($rationalMatch || $irrationalMatch))
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