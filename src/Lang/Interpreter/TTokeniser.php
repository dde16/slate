<?php declare(strict_types = 1);

namespace Slate\Lang\Interpreter {

    use Slate\Lang\Interpreter\Attribute\ComplexToken;
    use Slate\Lang\Interpreter\Attribute\CompoundToken;
    use Slate\Lang\Interpreter\Attribute\LateToken;
    use Slate\Lang\Interpreter\Attribute\LiteralToken;
    use Slate\Lang\Interpreter\Attribute\RangeToken;
    use Slate\Lang\Interpreter\Attribute\Token;
    use Slate\Lang\Interpreter\Tokeniser\TokenMatch;
    use Slate\Data\Iterator\BufferedIterator;

    use Generator;
    use Iterator;
    use ParseError;
    use RuntimeException;
    use Slate\Exception\ParseException;
    use Slate\Lang\Interpreter\Attribute\FallbackToken;
    use Slate\Lang\Interpreter\Trait\TComplexTokeniser;
    use Slate\Lang\Interpreter\Trait\TCompoundTokeniser;
    use Slate\Lang\Interpreter\Trait\TLateTokeniser;
    use Slate\Lang\Interpreter\Trait\TLiteralTokeniser;
    use Slate\Lang\Interpreter\Trait\TRaisable;
    use Slate\Lang\Interpreter\Trait\TRangeTokeniser;

    trait TTokeniser {
        use TComplexTokeniser;
        use TCompoundTokeniser;
        use TLateTokeniser;
        use TLiteralTokeniser;
        use TRangeTokeniser;
        use TRaisable;

        public object $code;

        public array $counters;

        public function intialiseCounters(): void {
            $design  = static::design();

            $this->counters = \Arr::merge(
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
        }

        public function matchToken(Token|int $definition, bool $raise = false, bool $raiseEof = false): ?TokenMatch {
            if(is_int($definition)) $definition = static::design()->tokens[$definition];

            $match = null;
            $eof   = $this->code->isEof();

            switch(true) {
                case \Cls::isSubclassInstanceOf($definition, ComplexToken::class):
                    $match = $this->matchComplexToken($definition, $raiseEof);
                    break;
                case \Cls::isSubclassInstanceOf($definition, CompoundToken::class):
                    $match = $this->matchCompoundToken($definition, $raiseEof);
                    break;
                case \Cls::isSubclassInstanceOf($definition, LateToken::class):
                    $match = $this->matchLateToken($definition, $raiseEof);
                    break;
                case \Cls::isSubclassInstanceOf($definition, LiteralToken::class):
                    $match = $this->matchLiteralToken($definition, $raiseEof);
                    break;
                case \Cls::isSubclassInstanceOf($definition, RangeToken::class):
                    $match = $this->matchRangeToken($definition, $raiseEof);
                    break;
            }

            if(($raise && !$match && !$eof)) 
                $this->raiseNonMatch($definition);

            if($match) {
                list($pointer, $length) = $match;
                
                $match = new TokenMatch(
                    code      : $this->code,
                    id        : $definition->parent->getValue(),
                    name      : $definition->parent->getName(),
                    length    : $length,
                    counters  : [
                        "pointer" => $pointer
                    ]
                );
            }

            return $match;
        }

        public function updateCounters(int $tokenID): void {
            $design  = static::design();

            $currentCounted = 0;
            $currentDecounted = 0;
            
            if($design->countTracked & $tokenID) {
                $trackId = $design->countTrackedTokenMap[$tokenID];
                
                if(($currentCounted & $trackId) === 0) {
                    $this->counters[$design->countTrackedMap[$trackId]]++;
                    $currentCounted ^= $trackId;
                }
            }
            
            if($design->levelTrackedOpen & $tokenID) {                            
                foreach($design->levelTrackedOpenMap[$tokenID] as $trackId) {
                    if(($currentCounted & $trackId) === 0) {
                        $this->counters[$design->levelTrackedMap[$trackId]]++;

                        $currentCounted ^= $trackId;
                    }
                }
            }
            
            if($design->levelTrackedClose & $tokenID) {
                foreach($design->levelTrackedCloseMap[$tokenID] as $trackId) {
                    if(($currentDecounted & $trackId) === 0) {
                        $this->counters[$design->levelTrackedMap[$trackId]]--;

                        $currentDecounted ^= $trackId;
                    }
                }
            }
            
            if($design->levelTrackedResetOpen & $tokenID) {
                foreach($design->levelTrackedResetOpenMap[$tokenID] as $counter) {
                    $this->counters[$counter]++;
                }
            }

            
            if($design->levelTrackedTable & $tokenID) {
                $this->counters[$design->levelTrackedTableMap[$tokenID]] = 0;
            }
            
            foreach($design->levelTrackedTableMap as $trackName) {
                $this->counters[$trackName]++;
            }


            if($design->levelTrackedResetClose & $tokenID) {
                foreach($design->levelTrackedResetCloseMap[$tokenID] as $counter) {
                    $this->counters[$counter] = 0;
                }
            }
        }

        public function getTokenGenerator(): Generator {
            $design  = static::design();

            $this->intialiseCounters();

            $lastPointer    = 0;
            $invalidToken   = false;

            while($invalidToken === false && !$this->code->isEof()) {
                $tokenMatch = null;

                foreach($design->tokens as $tokenID => $tokenDefinition) {

                    if($tokenDefinition !== null) {
                        $tokenMatch = $this->matchToken($tokenDefinition);

                        if($tokenMatch !== null) {
                            $this->updateCounters($tokenID);
                            
                            if(!($design->ignoring & $tokenID)) {
                                yield $tokenMatch;
                            }
                            
                            $this->counters["pointer"] = $this->code->tell();
                            
                            break;
                        }
                    }
                    else {
                        throw new \Error("Token $tokenID has no definition.");
                    }
                }

                /**
                 * If there is an invalid token or a pointer hasnt advanced (when the file isn't eof), raise an error.
                 */
                if(($tokenMatch === null || $this->counters["pointer"] === $lastPointer) && !$this->code->isEof()) {
                    $error = new \Error(
                        \Str::format(
                            "Invalid token '{}' at {}",
                            htmlentities(\Str::controls($this->code->current()), ENT_QUOTES),
                            strval($this->counters["pointer"])
                        )
                    );

                    throw $error;
                }

                $lastPointer = $this->counters["pointer"];
            }
        }

        public function getTokenBufferedIterator(): BufferedIterator {
            return (new BufferedIterator($this->getTokenGenerator()));
        }

        public function tokenise(bool $accumulative = false): BufferedIterator|Iterator {
            return $accumulative ? $this->getTokenBufferedIterator() : $this->getTokenGenerator();
        }
    }
}

?>