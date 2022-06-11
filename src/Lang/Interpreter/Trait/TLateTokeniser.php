<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Trait {

    use Slate\Lang\Interpreter\Attribute\LateToken;

    trait TLateTokeniser {
        use TRaisable;

        public function matchLateToken(LateToken $definition, bool $raiseEof = false): ?array {
            $this->code->anchor();

            if(!$definition->isDefined())
                throw new \Error(\Str::format(
                    "Late token '{}' has not been defined.",
                    $this->parent->getName()
                ));

            if(!($eof = $this->code->isEof())) {
                $start = $this->code->tell();

                if($this->code->match($definition->getExpression())) {
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