<?php

namespace Slate\Lang\Interpreter {

    use Slate\Lang\Interpreter\Attribute\ComplexToken;
    use Slate\Lang\Interpreter\Attribute\CompoundToken;
    use Slate\Lang\Interpreter\Attribute\LateToken;
    use Slate\Lang\Interpreter\Attribute\LiteralToken;
    use Slate\Lang\Interpreter\Attribute\RangeToken;
    use Slate\Lang\Interpreter\Attribute\Token;
    use Slate\Lang\Interpreter\Tokeniser\TokenMatch;
    use Slate\Data\Iterator\BufferedIterator;
    use Slate\Data\Iterator\StringIterator;
    use Slate\IO\File;

    use Generator;
    use Slate\Lang\Interpreter\ICodeable;

    trait TTokeniser {
        public object $code;

        public function matchComplexToken(ComplexToken $definition): ?array {            
            return $this->{$definition->getImplementor()->parent->getName()}();
        }

        public function matchCompoundToken(CompoundToken $definition): ?array {
            $this->code->anchor();

            if(!$this->code->isEof()) {
                $start = $this->code->tell();

                foreach($definition->getExpression() as [$choice, $length]) {
                    if(($match = $this->code->match($choice)) !== false) {
                        return [$start, $length];
                    }
                }
            }

            $this->code->revert();

            return null;
        }

        public function matchLateToken(LateToken $definition): ?array {
            if(!$definition->isDefined())
                throw new \Error(\Str::format(
                    "Late token '{}' has not been defined.",
                    $this->parent->getName()
                ));

            if(!$this->code->isEof()) {
                $start = $this->code->tell();

                if($this->code->match($definition->getExpression())) {
                    return [$start, $definition->getLength()];
                }
            }

            return null;

        }

        public function matchLiteralToken(LiteralToken $definition): ?array {
            if(!$this->code->isEof()) {
                $start = $this->code->tell();

                if($this->code->match($definition->getExpression())) {
                    return [$start, $definition->getLength()];
                }
            }

            return null;
        }

        public function matchRangeToken(RangeToken $definition): ?array {
            if(!$this->code->isEof()) {
                $charLiteral = $this->code->current();
    
                if($charLiteral) {
                    $charCode = ord($charLiteral);
    
                    if($charCode >= $definition->getFrom() && $charCode <= $definition->getTo()) {
                        $this->code->next();
                        return [$charLiteral, 1];
                    }
                }
            }
    
            return null;
        }

        public function matchToken(Token|int $definition): ?array {
            if(is_int($definition)) $definition = static::design()->tokens[$definition];

            $match = null;

            switch($definition::class) {
                case ComplexToken::class:
                    $match = $this->matchComplexToken($definition);
                    break;
                case CompoundToken::class:
                    $match = $this->matchCompoundToken($definition);
                    break;
                case LateToken::class:
                    $match = $this->matchLateToken($definition);
                    break;
                case LiteralToken::class:
                    $match = $this->matchLiteralToken($definition);
                    break;
                case RangeToken::class:
                    $match = $this->matchRangeToken($definition);
                    break;
            }
                    
            return $match;
        }

        public function getTokenGenerator(): Generator {
            $design  = static::design();

            $counters = \Arr::merge(
                [ "pointer" => $this->code->tell() ],
                \Arr::mapAssoc(
                    \Arr::merge(
                        $design->countTrackedMap,
                        $design->levelTrackedMap,
                        $design->levelTrackedResetMap
                    ),
                    function($key, $name) {
                        return [$name, 0];
                    }
                )
            );

            $lastPointer    = 0;
            $invalidToken   = false;
            $currentToken   = 0;
            $lastToken      = 0;

            while($invalidToken === false && !$this->code->isEof()) {
                $tokenMatch = null;


                foreach($design->tokens as $tokenId => $tokenDefinition) {
                    if($tokenDefinition !== null) {
                        $tokenRawMatch = null;
                        $tokenRawMatch = $this->matchToken($tokenDefinition);

                        if($tokenRawMatch !== null) {
                            list($tokenMatchPointer, $tokenMatchLength) = $tokenRawMatch;

                            $tokenMatch = new TokenMatch(
                                code      : $this->code,
                                id        : $tokenId,
                                name      : $tokenDefinition->parent->getName(),
                                length    : $tokenMatchLength,
                                counters  : \Arr::merge(
                                    $counters,
                                    [ "pointer" => $tokenMatchPointer ]
                                )
                            );

                            $currentToken = $tokenId;
                            $currentCounted = 0;
                            $currentDecounted = 0;
                            
                            if($design->countTracked & $tokenId) {
                                $trackId = $design->countTrackedTokenMap[$tokenId];
                                
                                if(($currentCounted & $trackId) === 0) {
                                    $counters[$design->countTrackedMap[$trackId]]++;
                                    $currentCounted ^= $trackId;
                                }
                            }
                            
                            if($design->levelTrackedOpen & $tokenId) {                            
                                foreach($design->levelTrackedOpenMap[$tokenId] as $trackId) {
                                    if(($currentCounted & $trackId) === 0) {
                                        $counters[$design->levelTrackedMap[$trackId]]++;

                                        $currentCounted ^= $trackId;
                                    }
                                }
                            }
                            
                            if($design->levelTrackedClose & $tokenId) {
                                foreach($design->levelTrackedCloseMap[$tokenId] as $trackId) {
                                    if(($currentDecounted & $trackId) === 0) {
                                        $counters[$design->levelTrackedMap[$trackId]]--;

                                        $currentDecounted ^= $trackId;
                                    }
                                }
                            }
                            
                            if($design->levelTrackedResetOpen & $tokenId) {
                                foreach($design->levelTrackedResetOpenMap[$tokenId] as $counter) {
                                    $counters[$counter]++;
                                }
                            }

                            
                            if($design->levelTrackedTable & $tokenId) {
                                $counters[$design->levelTrackedTableMap[$tokenId]] = 0;
                            }
                            
                            foreach($design->levelTrackedTableMap as $trackTokenId => $trackName) {
                                $counters[$trackName]++;
                            }


                            if($design->levelTrackedResetClose & $tokenId) {
                                foreach($design->levelTrackedResetCloseMap[$tokenId] as $counter) {
                                    $counters[$counter] = 0;
                                }
                            }
                            
                            if(!($design->ignoring & $tokenId)) {
                                yield $tokenMatch;
                            }
                            
                            $counters["pointer"] = $this->code->tell();
                            
                            break;
                        }
                    }
                    else {
                        throw new \Error("Token $tokenId has no definition.");
                    }
                }

                if($tokenMatch === null || $counters["pointer"] === $lastPointer) {
                    $error = new \Error(
                        \Str::format(
                            "Invalid token '{}' at {}",
                            htmlentities(\Str::swap(
                                $this->code->current(),
                                ["\r" => "\\r", "\f" => "\\f", "\n" => "\\n", "\t" => "\\t"],
                            ), ENT_QUOTES),
                            strval($counters["pointer"])
                        )
                    );

                    throw $error;
                }

                $lastToken = $currentToken;
                $lastPointer = $counters["pointer"];
            }
        }

        public function getTokenBufferedIterator(): BufferedIterator {
            return (new BufferedIterator($this->getTokenGenerator()));
        }

        public function tokenise(bool $accumulative = false) {
            return $accumulative ? $this->getTokenBufferedIterator() : $this->getTokenGenerator();
        }
    }
}

?>