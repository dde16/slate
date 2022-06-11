<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter\Trait {
    use Slate\Lang\Interpreter\Attribute\ComplexToken;

    trait TComplexTokeniser {
        use TRaisable;

        public function matchComplexToken(ComplexToken $definition, bool $raiseEof = false): ?array {            
            return $this->{$definition->getImplementor()->parent->getName()}($raiseEof);
        }
    }
}

?>