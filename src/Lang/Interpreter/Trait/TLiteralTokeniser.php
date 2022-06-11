<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Trait {
    use Slate\Lang\Interpreter\Attribute\LiteralToken;

    trait TLiteralTokeniser {
        use TRaisable;

        public function matchLiteralToken(LiteralToken $definition, bool $raiseEof = false): ?array {
            $this->code->anchor();
            
            if(!($eof = $this->code->isEof())) {
                $start = $this->code->tell();

                if($this->code->match($definition->getExpression(), $definition->isExact())) {
                    return [$start, $definition->getLength()];
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