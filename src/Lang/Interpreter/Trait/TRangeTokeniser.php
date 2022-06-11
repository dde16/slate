<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Trait {
    use Slate\Lang\Interpreter\Attribute\RangeToken;

    trait TRangeTokeniser {
        use TRaisable;

        public function matchRangeToken(RangeToken $definition, bool $raiseEof = false): ?array {
            $this->code->anchor();
            
            if(!($eof = $this->code->isEof())) {
                $offset = $this->code->tell();
                $charLiteral = $this->code->current();
    
                if($charLiteral) {
                    $charCode = ord($charLiteral);
    
                    if($charCode >= $definition->getFrom() && $charCode <= $definition->getTo()) {
                        $this->code->next();
                        return [$offset, 1];
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