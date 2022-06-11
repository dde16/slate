<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Trait {
    use Slate\Lang\Interpreter\Attribute\CompoundToken;

    trait TCompoundTokeniser {
        use TRaisable;

        public function matchCompoundToken(CompoundToken $definition, bool $raiseEof = false): ?array {
            $this->code->anchor();

            if(!($eof = $this->code->isEof())) {
                $start = $this->code->tell();

                foreach($definition->getExpression() as [$choice, $length]) {
                    if($this->code->match($choice) !== false) {
                        return [$start, $length];
                    }
                }
            }

            $this->code->revert();

            if($eof && $raiseEof)
                $this->raiseEof();

            return null;
        }
    }
}

?>